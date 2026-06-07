<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationEvent;
use App\Models\Organization\OrganizationEventRegistration;
use App\Models\Organization\OrganizationPaymentGateway;
use App\Models\Organization\OrganizationMember;
use App\Models\User\Notification;
use App\Services\BillingService;
use App\Services\MercadoPagoService;
use App\Services\NotificationService;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OrganizationEventRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();
        if (! $event) {
            return response()->json(['message' => 'event_not_found'], 404);
        }

        $registrations = OrganizationEventRegistration::where('organization_event_id', $event->id)
            ->whereIn('payment_status', ['free', 'confirmed'])
            ->with('user:id,name,username')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'payment_status' => $r->payment_status,
                'confirmed_at' => $r->confirmed_at,
                'registered_at' => $r->created_at,
                'user' => $r->user ? [
                    'id' => $r->user->id,
                    'name' => $r->user->name,
                    'username' => $r->user->username,
                ] : null,
            ]);

        return response()->json(['message' => $registrations->values()], 200);
    }

    public function store(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        if ($organization->billing_blocked_at) {
            return response()->json(['message' => 'org_billing_blocked'], 422);
        }

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();
        if (! $event) {
            return response()->json(['message' => 'event_not_found'], 404);
        }

        if ($event->initialized || $event->finished) {
            return response()->json(['message' => 'registrations_closed'], 422);
        }

        if ($event->max_registrations) {
            $count = OrganizationEventRegistration::where('organization_event_id', $event->id)
                ->whereIn('payment_status', ['free', 'confirmed', 'pending'])
                ->count();
            if ($count >= $event->max_registrations) {
                return response()->json(['message' => 'event_full'], 422);
            }
        }

        $userId = $request->user('sanctum')->id;

        $exists = OrganizationEventRegistration::where('organization_event_id', $event->id)
            ->where('user_id', $userId)
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'already_registered'], 409);
        }

        $isFree    = (float) $event->fee === 0.0;
        $gateways  = $isFree ? collect() : $this->eligibleGateways(
            OrganizationPaymentGateway::where('organization_id', $organization->id)->where('active', true)->get(),
            $event->currency ?? 'brl'
        );

        if (! $isFree && $gateways->isEmpty()) {
            $this->notifyNoGateway($organization, $event);
            return response()->json(['message' => 'no_compatible_gateway'], 422);
        }

        // If multiple gateways and buyer hasn't chosen yet, let them pick
        $chosenGatewayKey = $request->input('gateway');
        $needsGatewayChoice = ! $isFree && $gateways->count() > 1 && ! $chosenGatewayKey;

        $gateway = $needsGatewayChoice
            ? null
            : ($chosenGatewayKey
                ? $gateways->firstWhere('gateway', $chosenGatewayKey)
                : $gateways->first());

        $registration = DB::transaction(function () use ($request, $event, $userId, $isFree, $gateway, $needsGatewayChoice) {
            $reg = OrganizationEventRegistration::create([
                'organization_event_id' => $event->id,
                'user_id' => $userId,
                'team_id' => $request->input('team_id'),
                'form_data' => $request->input('form_data'),
                'payment_status' => $isFree ? 'free' : 'pending',
                'confirmed_at' => $isFree ? now() : null,
                'gateway' => null,
            ]);

            // Stripe Connect auto-collects fee via application_fee_amount — no billing_item needed.
            // When gateway not yet chosen, record billing item conservatively (MP flow assumption).
            $isStripeConnectPaid = ! $isFree && $gateway && $gateway->gateway === 'stripe_connect';
            if (! $isStripeConnectPaid && ! $needsGatewayChoice) {
                $reg->load('event.organization');
                app(BillingService::class)->recordRegistrationFee($reg, (float) $event->fee);
            }

            return $reg;
        });

        if ($isFree) {
            return response()->json([
                'message' => 'registered',
                'data' => ['id' => $registration->id, 'payment_status' => 'free', 'payment_url' => null],
            ], 201);
        }

        // Return available gateways for buyer to choose
        if ($needsGatewayChoice) {
            return response()->json([
                'message' => 'registered',
                'data' => [
                    'id' => $registration->id,
                    'payment_status' => 'pending',
                    'payment_url' => null,
                    'available_gateways' => $gateways->pluck('gateway')->values(),
                ],
            ], 201);
        }

        if (! $gateway) {
            return response()->json([
                'message' => 'registered',
                'data' => ['id' => $registration->id, 'payment_status' => 'pending', 'payment_url' => null, 'warning' => 'no_gateway'],
            ], 201);
        }

        $viewerBase = config('app.viewer_url', 'http://localhost:5173');
        $eventFee   = (float) $event->fee;
        $feeAmount  = app(BillingService::class)->calculateFee($eventFee);

        try {
            $paymentUrl = $this->createPaymentUrl($gateway, $event, $organization, $registration, $eventFee, $feeAmount, $viewerBase);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'registered',
                'data' => ['id' => $registration->id, 'payment_status' => 'pending', 'payment_url' => null, 'warning' => 'gateway_error'],
            ], 201);
        }

        return response()->json([
            'message' => 'registered',
            'data' => ['id' => $registration->id, 'payment_status' => 'pending', 'payment_url' => $paymentUrl],
        ], 201);
    }

    public function checkPayment(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $organization) return response()->json(['message' => 'org_not_found'], 404);

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();
        if (! $event) return response()->json(['message' => 'event_not_found'], 404);

        $userId = $request->user('sanctum')->id;
        $registration = OrganizationEventRegistration::where('organization_event_id', $event->id)
            ->where('user_id', $userId)
            ->first();
        if (! $registration) return response()->json(['message' => 'not_registered'], 404);

        if (in_array($registration->payment_status, ['confirmed', 'free'])) {
            return response()->json(['message' => ['status' => $registration->payment_status]], 200);
        }

        $gwQuery = OrganizationPaymentGateway::where('organization_id', $organization->id)->where('active', true);
        $gateway = $registration->gateway
            ? $gwQuery->where('gateway', $registration->gateway)->first()
            : $gwQuery->first();

        if ($gateway && $registration->gateway_preference_id) {
            try {
                if ($gateway->gateway === 'mercadopago') {
                    $token   = Crypt::decryptString($gateway->access_token);
                    $results = app(MercadoPagoService::class)
                        ->searchPaymentsByExternalReference($token, $registration->id);
                    foreach ($results['results'] ?? [] as $payment) {
                        if ($payment['status'] === 'approved') {
                            $registration->update([
                                'payment_status'    => 'confirmed',
                                'gateway_payment_id'=> (string) $payment['id'],
                                'gateway'           => 'mercadopago',
                                'confirmed_at'      => now(),
                            ]);
                            return response()->json(['message' => ['status' => 'confirmed']], 200);
                        }
                    }
                } elseif ($gateway->gateway === 'stripe_connect') {
                    $session = app(StripeConnectService::class)->getCheckoutSession(
                        $registration->gateway_preference_id,
                        $gateway->gateway_user_id
                    );
                    if (($session['payment_status'] ?? '') === 'paid') {
                        $paymentIntentId = $session['payment_intent'] ?? $registration->gateway_preference_id;
                        $registration->update([
                            'payment_status'    => 'confirmed',
                            'gateway_payment_id'=> $paymentIntentId,
                            'gateway'           => 'stripe_connect',
                            'confirmed_at'      => now(),
                        ]);
                        return response()->json(['message' => ['status' => 'confirmed']], 200);
                    }
                }
            } catch (\Exception $e) {
                // gateway unreachable — fall through with current status
            }
        }

        return response()->json(['message' => ['status' => $registration->payment_status]], 200);
    }

    public function retryPayment(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $organization) return response()->json(['message' => 'org_not_found'], 404);

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();
        if (! $event) return response()->json(['message' => 'event_not_found'], 404);

        $userId = $request->user('sanctum')->id;
        $registration = OrganizationEventRegistration::where('organization_event_id', $event->id)
            ->where('user_id', $userId)
            ->first();
        if (! $registration) return response()->json(['message' => 'not_registered'], 404);
        if ($registration->payment_status !== 'pending') {
            return response()->json(['message' => 'not_pending'], 422);
        }

        $chosenGatewayKey = $request->input('gateway');
        $allGateways = $this->eligibleGateways(
            OrganizationPaymentGateway::where('organization_id', $organization->id)->where('active', true)->get(),
            $event->currency ?? 'brl'
        );

        if ($allGateways->isEmpty()) return response()->json(['message' => 'no_gateway'], 422);

        // Multiple gateways and buyer hasn't chosen → return list to let them pick
        if ($allGateways->count() > 1 && ! $chosenGatewayKey) {
            return response()->json([
                'message' => ['available_gateways' => $allGateways->pluck('gateway')->values()],
            ], 200);
        }

        $gateway = $chosenGatewayKey
            ? $allGateways->firstWhere('gateway', $chosenGatewayKey)
            : $allGateways->first();
        if (! $gateway) return response()->json(['message' => 'no_gateway'], 422);

        $viewerBase = config('app.viewer_url', 'http://localhost:5173');
        $eventFee   = (float) $event->fee;
        $feeAmount  = app(BillingService::class)->calculateFee($eventFee);

        // Multi-gateway: billing item deferred until gateway chosen — record it now for MP
        if (is_null($registration->gateway) && $gateway->gateway === 'mercadopago') {
            $registration->load('event.organization');
            app(BillingService::class)->recordRegistrationFee($registration, $eventFee);
        }

        try {
            $paymentUrl = $this->createPaymentUrl($gateway, $event, $organization, $registration, $eventFee, $feeAmount, $viewerBase);
        } catch (\Exception $e) {
            return response()->json(['message' => 'gateway_error'], 500);
        }

        return response()->json(['message' => ['payment_url' => $paymentUrl]], 200);
    }

    private function notifyNoGateway($organization, $event): void
    {
        // Rate-limit: skip if already notified org members in the last hour
        $memberIds = OrganizationMember::where('organization_id', $organization->id)
            ->whereIn('role', ['owner', 'admin', 'financial'])
            ->pluck('user_id');

        $alreadySent = Notification::whereIn('user_id', $memberIds)
            ->where('title', 'notification.no_gateway_configured')
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($alreadySent) return;

        $route = "/org/{$organization->route}/manage/finances";
        foreach ($memberIds as $userId) {
            NotificationService::send($userId, 'notification.no_gateway_configured', [
                'org'   => $organization->name,
                'event' => $event->name,
            ], $route);
        }
    }

    private function eligibleGateways(\Illuminate\Support\Collection $gateways, string $currency): \Illuminate\Support\Collection
    {
        // USD/EUR events must use Stripe Connect (MP only supports BRL/ARS/etc)
        if (in_array(strtolower($currency), ['usd', 'eur'])) {
            return $gateways->filter(fn ($gw) => $gw->gateway === 'stripe_connect')->values();
        }
        return $gateways->values();
    }

    private function createPaymentUrl($gateway, $event, $organization, $registration, float $eventFee, float $feeAmount, string $viewerBase): string
    {
        $successUrl = $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=success";
        $failureUrl = $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=failure";

        if ($gateway->gateway === 'mercadopago') {
            $accessToken = Crypt::decryptString($gateway->access_token);
            $pref = app(MercadoPagoService::class)->createPreference(
                $accessToken,
                $event->name,
                $eventFee,
                $event->currency ?? 'brl',
                $registration->id,
                $successUrl,
                $failureUrl,
                $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=pending",
                $feeAmount
            );
            $registration->update(['gateway_preference_id' => $pref['id'], 'gateway' => 'mercadopago']);
            return $pref['init_point'];
        }

        // Stripe Connect
        $amountCents = (int) round($eventFee * 100);
        $feeCents    = (int) round($feeAmount * 100);
        $currency    = strtolower($event->currency ?? 'brl');
        $session = app(StripeConnectService::class)->createCheckoutSession(
            $gateway->gateway_user_id,
            $event->name,
            $amountCents,
            $currency,
            $feeCents,
            $registration->id,
            $successUrl,
            $failureUrl
        );
        $registration->update(['gateway_preference_id' => $session['id'], 'gateway' => 'stripe_connect']);
        return $session['url'];
    }

    public function destroy(Request $request)
    {
        $organization = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $organization) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        $event = OrganizationEvent::where('organization_id', $organization->id)
            ->where('route', $request->route('eventRoute'))
            ->first();
        if (! $event) {
            return response()->json(['message' => 'event_not_found'], 404);
        }

        if ($event->initialized || $event->finished) {
            return response()->json(['message' => 'registrations_closed'], 422);
        }

        $userId = $request->user('sanctum')->id;
        $registration = OrganizationEventRegistration::where('organization_event_id', $event->id)
            ->where('user_id', $userId)
            ->first();

        if (! $registration) {
            return response()->json(['message' => 'not_registered'], 404);
        }

        if ($registration->payment_status === 'confirmed' && (float) $event->fee > 0) {
            return response()->json(['message' => 'cannot_unregister_paid'], 422);
        }

        $registration->delete();

        return response()->json(['message' => 'unregistered'], 200);
    }
}
