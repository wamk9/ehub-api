<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationMember;
use App\Models\Organization\OrganizationPaymentGateway;
use App\Services\MercadoPagoService;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class OrganizationPaymentGatewayController extends Controller
{
    private function canManageGateway(string $orgId, string $userId): bool
    {
        $member = OrganizationMember::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->first();

        return $member && in_array($member->role, ['owner', 'admin', 'financial']);
    }

    public function index(Request $request)
    {
        $org = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $gateways = OrganizationPaymentGateway::where('organization_id', $org->id)
            ->get()
            ->map(fn ($g) => [
                'gateway' => $g->gateway,
                'gateway_user_id' => $g->gateway_user_id,
                'active' => $g->active,
                'expires_at' => $g->expires_at,
            ]);

        return response()->json(['message' => $gateways], 200);
    }

    public function connect(Request $request)
    {
        $gateway = $request->route('gateway');
        if (! in_array($gateway, ['mercadopago', 'stripe_connect'])) {
            return response()->json(['message' => 'invalid_gateway'], 422);
        }

        $org = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        if (! $this->canManageGateway($org->id, $request->user('sanctum')->id)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $state = base64_encode(json_encode(['org_id' => $org->id, 'gateway' => $gateway]));
        Cache::put("gateway_oauth_state_{$state}", $org->id, now()->addMinutes(15));

        $url = match ($gateway) {
            'mercadopago' => app(MercadoPagoService::class)->getOAuthUrl($state),
            'stripe_connect' => app(StripeConnectService::class)->getOAuthUrl($state),
        };

        return response()->json(['message' => $url], 200);
    }

    public function callback(Request $request)
    {
        $gateway = $request->route('gateway');
        $state = $request->query('state');
        $viewerBase = config('app.viewer_url', 'http://localhost:5173');

        if ($gateway === 'mercadopago') {
            $orgId = Cache::get("gateway_oauth_state_{$state}");
            if (! $orgId) {
                return redirect($viewerBase.'/payment/gateway/error?reason=invalid_state');
            }

            try {
                $data = app(MercadoPagoService::class)->exchangeCode($request->query('code'));
            } catch (\Exception $e) {
                return redirect($viewerBase.'/payment/gateway/error?reason=exchange_failed');
            }

            OrganizationPaymentGateway::updateOrCreate(
                ['organization_id' => $orgId, 'gateway' => 'mercadopago'],
                [
                    'access_token' => Crypt::encryptString($data['access_token']),
                    'refresh_token' => isset($data['refresh_token']) ? Crypt::encryptString($data['refresh_token']) : null,
                    'gateway_user_id' => $data['user_id'] ?? null,
                    'public_key' => $data['public_key'] ?? null,
                    'expires_at' => isset($data['expires_in']) ? now()->addSeconds($data['expires_in']) : null,
                    'active' => true,
                ]
            );

            Cache::forget("gateway_oauth_state_{$state}");
        }

        if ($gateway === 'stripe_connect') {
            $orgId = Cache::get("gateway_oauth_state_{$state}");
            if (! $orgId) {
                return redirect($viewerBase.'/payment/gateway/error?reason=invalid_state');
            }

            $code = $request->query('code');
            if (! $code) {
                $error = $request->query('error_description', 'access_denied');

                return redirect($viewerBase.'/payment/gateway/error?reason='.urlencode($error));
            }

            try {
                $data = app(StripeConnectService::class)->exchangeCode($code);
            } catch (\Exception $e) {
                Log::error('Stripe Connect callback error: '.$e->getMessage());

                return redirect($viewerBase.'/payment/gateway/error?reason=exchange_failed');
            }

            OrganizationPaymentGateway::updateOrCreate(
                ['organization_id' => $orgId, 'gateway' => 'stripe_connect'],
                [
                    'access_token' => Crypt::encryptString($data['access_token']),
                    'refresh_token' => null,
                    'gateway_user_id' => $data['stripe_user_id'],
                    'active' => true,
                    'expires_at' => null,
                ]
            );

            Cache::forget("gateway_oauth_state_{$state}");
        }

        $org = Organization::find($orgId ?? null);
        $orgRoute = $org?->route ?? '';

        return redirect($viewerBase."/org/{$orgRoute}/manage/finances?connected={$gateway}");
    }

    public function disconnect(Request $request)
    {
        $gateway = $request->route('gateway');
        $org = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        if (! $this->canManageGateway($org->id, $request->user('sanctum')->id)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $record = OrganizationPaymentGateway::where('organization_id', $org->id)
            ->where('gateway', $gateway)
            ->first();

        if ($record && $gateway === 'stripe_connect' && $record->gateway_user_id) {
            try {
                app(StripeConnectService::class)->disconnect($record->gateway_user_id);
            } catch (\Exception $e) {
                Log::warning('Stripe Connect deauthorize failed: '.$e->getMessage());
            }
        }

        OrganizationPaymentGateway::where('organization_id', $org->id)
            ->where('gateway', $gateway)
            ->delete();

        return response()->json(['message' => 'disconnected'], 200);
    }
}
