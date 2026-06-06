<?php

namespace App\Console\Commands;

use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationBillingInvoice;
use App\Services\BillingService;
use App\Services\NotificationService;
use App\Services\StripeService;
use Illuminate\Console\Command;

class BillingGenerateMonthly extends Command
{
    protected $signature   = 'billing:generate-monthly {--cycle= : Billing cycle YYYY-MM (default: last month)}';
    protected $description = 'Generate and charge monthly billing invoices for all organizations';

    public function handle(BillingService $billing, StripeService $stripe): int
    {
        $cycle = $this->option('cycle') ?? now()->subMonth()->format('Y-m');
        $this->info("Generating billing for cycle: {$cycle}");

        $results = $billing->generateMonthlyInvoices($cycle);

        if (empty($results)) {
            $this->info('No unbilled items found.');
            return 0;
        }

        foreach ($results as $item) {
            $invoice = OrganizationBillingInvoice::find($item['invoice_id']);
            $org     = Organization::find($item['organization_id']);

            if (!$invoice || $invoice->status !== 'pending' || $invoice->total_amount <= 0) {
                $this->line("  [{$org?->name}] {$cycle}: R\$ " . number_format($item['total'], 2, ',', '.') . ' — skipped (empty or already processed)');
                continue;
            }

            if (!$org->stripe_customer_id) {
                $this->warn("  [{$org->name}] No Stripe customer — skipping charge. Invoice marked pending.");
                continue;
            }

            $lineItems = $invoice->items->map(fn($i) => [
                'amount'      => (float) $i->fee_amount,
                'description' => match ($i->billing_type) {
                    'registration_paid' => 'Taxa eHub — inscrição paga (2%, mín R$1)',
                    'registration_free' => 'Taxa eHub — inscrição em evento gratuito (R$1)',
                    default             => 'Taxa eHub',
                },
            ])->values()->toArray();

            try {
                $invoice = $stripe->chargeInvoice($invoice->load('organization'), $lineItems);
                $status  = $invoice->status;
                $this->line("  [{$org->name}] {$cycle}: R\$ " . number_format($item['total'], 2, ',', '.') . " — {$status}");

                NotificationService::sendToOrgRoles(
                    $org->id,
                    ['owner', 'admin', 'financial'],
                    'notification.billing_invoice',
                    ['cycle' => $cycle, 'org' => $org->name],
                    '/org/' . $org->route . '/manage'
                );
            } catch (\Exception $e) {
                $this->error("  [{$org->name}] Charge failed: " . $e->getMessage());
            }
        }

        $this->info('Done.');
        return 0;
    }
}
