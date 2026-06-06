<?php

namespace App\Services;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationBillingInvoice;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createOrGetCustomer(Organization $org): string
    {
        if ($org->stripe_customer_id) {
            return $org->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'name'     => $org->name,
            'metadata' => ['organization_id' => $org->id, 'route' => $org->route],
        ]);

        $org->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    public function createSetupIntent(string $customerId): string
    {
        $intent = $this->stripe->setupIntents->create([
            'customer'            => $customerId,
            'payment_method_types' => ['card'],
        ]);

        return $intent->client_secret;
    }

    public function getDefaultPaymentMethod(string $customerId): ?string
    {
        $customer = $this->stripe->customers->retrieve($customerId, ['expand' => ['default_source']]);
        return $customer->invoice_settings->default_payment_method ?? null;
    }

    public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId): void
    {
        $this->stripe->paymentMethods->attach($paymentMethodId, ['customer' => $customerId]);
        $this->stripe->customers->update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);
    }

    /**
     * Create a Stripe invoice and immediately attempt to charge.
     * Returns the invoice model with updated status.
     */
    public function chargeInvoice(OrganizationBillingInvoice $invoiceModel, array $lineItems): OrganizationBillingInvoice
    {
        $customerId = $invoiceModel->organization->stripe_customer_id;

        if (!$customerId) {
            throw new \RuntimeException('Organization has no Stripe customer ID.');
        }

        // Create invoice items
        foreach ($lineItems as $item) {
            $this->stripe->invoiceItems->create([
                'customer'    => $customerId,
                'amount'      => (int) round($item['amount'] * 100), // centavos
                'currency'    => 'brl',
                'description' => $item['description'],
            ]);
        }

        // Create and finalize invoice
        $invoice = $this->stripe->invoices->create([
            'customer'         => $customerId,
            'auto_advance'     => false,
            'collection_method' => 'charge_automatically',
            'metadata'         => [
                'organization_id' => $invoiceModel->organization_id,
                'billing_cycle'   => $invoiceModel->billing_cycle,
                'invoice_id'      => $invoiceModel->id,
            ],
        ]);

        $invoice = $this->stripe->invoices->finalizeInvoice($invoice->id);

        $invoiceModel->update([
            'stripe_invoice_id' => $invoice->id,
        ]);

        // Attempt payment
        try {
            $paid = $this->stripe->invoices->pay($invoice->id);
            $invoiceModel->update([
                'status'                     => 'paid',
                'stripe_payment_intent_id'   => $paid->payment_intent,
                'paid_at'                    => now(),
            ]);
        } catch (\Stripe\Exception\CardException $e) {
            $invoiceModel->update([
                'status'     => 'failed',
                'failed_at'  => now(),
            ]);
        }

        return $invoiceModel->fresh();
    }
}
