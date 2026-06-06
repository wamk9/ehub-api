<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationEvent;
use App\Models\Organization\OrganizationEventRegistration;
use App\Models\Organization\OrganizationPaymentGateway;
use App\Services\BillingService;
use App\Services\MercadoPagoService;
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
            ->with('user:id,name,username,avatar')
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
                    'avatar' => $r->user->avatar,
                ] : null,
            ]);

        return response()->json(['message' => $registrations], 200);
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

        $isFree = (float) $event->fee === 0.0;
        $gateway = $isFree ? null : OrganizationPaymentGateway::where('organization_id', $organization->id)
            ->where('active', true)
            ->first();

        $registration = DB::transaction(function () use ($request, $event, $userId, $isFree, $gateway) {
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
            // All other cases (MP paid, free events, no gateway) need a billing_item for monthly invoice.
            $isStripeConnectPaid = ! $isFree && $gateway && $gateway->gateway === 'stripe_connect';
            if (! $isStripeConnectPaid) {
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

        if (! $gateway) {
            return response()->json([
                'message' => 'registered',
                'data' => ['id' => $registration->id, 'payment_status' => 'pending', 'payment_url' => null, 'warning' => 'no_gateway'],
            ], 201);
        }

        $viewerBase = config('app.viewer_url', 'http://localhost:5173');
        $eventFee = (float) $event->fee;
        $feeAmount = app(BillingService::class)->calculateFee($eventFee);

        try {
            if ($gateway->gateway === 'mercadopago') {
                $accessToken = Crypt::decryptString($gateway->access_token);
                $pref = app(MercadoPagoService::class)->createPreference(
                    $accessToken,
                    $event->name,
                    $eventFee,
                    $event->currency ?? 'brl',
                    $registration->id,
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=success",
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=failure",
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=pending",
                    $feeAmount
                );
                $registration->update(['gateway_preference_id' => $pref['id'], 'gateway' => 'mercadopago']);
                $paymentUrl = $pref['init_point'];
            } else {
                // Stripe Connect
                $connectedAccountId = $gateway->gateway_user_id;
                $amountCents = (int) round($eventFee * 100);
                $feeCents = (int) round($feeAmount * 100);
                $currency = strtolower($event->currency ?? 'brl');
                $session = app(StripeConnectService::class)->createCheckoutSession(
                    $connectedAccountId,
                    $event->name,
                    $amountCents,
                    $currency,
                    $feeCents,
                    $registration->id,
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=success",
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=failure"
                );
                $registration->update(['gateway_preference_id' => $session['id'], 'gateway' => 'stripe_connect']);
                $paymentUrl = $session['url'];
            }
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

        $gateway = OrganizationPaymentGateway::where('organization_id', $organization->id)
            ->where('active', true)->first();

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

        $gateway = OrganizationPaymentGateway::where('organization_id', $organization->id)
            ->where('active', true)->first();
        if (! $gateway) return response()->json(['message' => 'no_gateway'], 422);

        $viewerBase = config('app.viewer_url', 'http://localhost:5173');
        $eventFee   = (float) $event->fee;
        $feeAmount  = app(BillingService::class)->calculateFee($eventFee);

        try {
            if ($gateway->gateway === 'mercadopago') {
                $accessToken = Crypt::decryptString($gateway->access_token);
                $pref = app(MercadoPagoService::class)->createPreference(
                    $accessToken,
                    $event->name,
                    $eventFee,
                    $event->currency ?? 'brl',
                    $registration->id,
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=success",
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=failure",
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=pending",
                    $feeAmount
                );
                $registration->update(['gateway_preference_id' => $pref['id']]);
                $paymentUrl = $pref['init_point'];
            } else {
                $connectedAccountId = $gateway->gateway_user_id;
                $amountCents = (int) round($eventFee * 100);
                $feeCents    = (int) round($feeAmount * 100);
                $currency    = strtolower($event->currency ?? 'brl');
                $session = app(StripeConnectService::class)->createCheckoutSession(
                    $connectedAccountId,
                    $event->name,
                    $amountCents,
                    $currency,
                    $feeCents,
                    $registration->id,
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=success",
                    $viewerBase."/org/{$organization->route}/event/{$event->route}?payment=failure"
                );
                $registration->update(['gateway_preference_id' => $session['id']]);
                $paymentUrl = $session['url'];
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'gateway_error'], 500);
        }

        return response()->json(['message' => ['payment_url' => $paymentUrl]], 200);
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
