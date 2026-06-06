<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Organization\OrganizationBillingInvoice;
use App\Models\Organization\OrganizationEventRegistration;
use App\Models\Organization\OrganizationPaymentGateway;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function mercadopago(Request $request)
    {
        $type = $request->input('type') ?? $request->input('topic');

        if ($type !== 'payment') {
            return response()->json(['ok' => true], 200);
        }

        $paymentId = $request->input('data.id') ?? $request->input('id');
        if (! $paymentId) {
            return response()->json(['ok' => true], 200);
        }

        $registration = OrganizationEventRegistration::where('gateway_payment_id', $paymentId)
            ->orWhere('gateway_preference_id', $paymentId)
            ->first();

        if (! $registration) {
            try {
                $gateway = OrganizationPaymentGateway::where('gateway', 'mercadopago')
                    ->where('active', true)
                    ->first();

                if ($gateway) {
                    $token = Crypt::decryptString($gateway->access_token);
                    $payment = app(MercadoPagoService::class)->getPayment($token, $paymentId);
                    $regId = $payment['external_reference'] ?? null;
                    if ($regId) {
                        $registration = OrganizationEventRegistration::find($regId);
                    }
                }
            } catch (\Exception $e) {
                Log::error('MP webhook error: '.$e->getMessage());
            }
        }

        if (! $registration) {
            return response()->json(['ok' => true], 200);
        }

        $this->confirmRegistration($registration, $paymentId, 'mercadopago');

        return response()->json(['ok' => true], 200);
    }

    /**
     * Stripe Connect webhook — handles checkout.session.completed events on connected accounts.
     * Configured as a Connect webhook in Stripe Dashboard.
     */
    public function stripeConnect(Request $request)
    {
        $secret = config('services.stripe.connect_webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $regId = $session->client_reference_id;

            if ($regId) {
                $registration = OrganizationEventRegistration::find($regId);
                if ($registration && $session->payment_status === 'paid') {
                    $paymentIntentId = $session->payment_intent ?? $session->id;
                    $this->confirmRegistration($registration, $paymentIntentId, 'stripe_connect');
                }
            }
        }

        return response()->json(['ok' => true], 200);
    }

    /**
     * Stripe platform webhook — handles invoice events for org monthly billing.
     */
    public function stripe(Request $request)
    {
        $secret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Exception $e) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        if ($event->type === 'invoice.payment_succeeded') {
            $stripeInvoiceId = $event->data->object->id;
            OrganizationBillingInvoice::where('stripe_invoice_id', $stripeInvoiceId)
                ->update(['status' => 'paid', 'paid_at' => now()]);
        }

        if ($event->type === 'invoice.payment_failed') {
            $stripeInvoiceId = $event->data->object->id;
            OrganizationBillingInvoice::where('stripe_invoice_id', $stripeInvoiceId)
                ->update(['status' => 'failed', 'failed_at' => now()]);
        }

        return response()->json(['ok' => true], 200);
    }

    private function confirmRegistration(
        OrganizationEventRegistration $registration,
        string $paymentId,
        string $gateway
    ): void {
        if ($registration->payment_status === 'confirmed') {
            return;
        }

        $registration->update([
            'payment_status' => 'confirmed',
            'gateway_payment_id' => $paymentId,
            'gateway' => $gateway,
            'confirmed_at' => now(),
        ]);
    }
}
