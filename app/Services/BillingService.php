<?php

namespace App\Services;

use App\Models\Organization\OrganizationBillingInvoice;
use App\Models\Organization\OrganizationBillingItem;
use App\Models\Organization\OrganizationEventRegistration;
use Carbon\Carbon;

class BillingService
{
    // Platform fee config
    const FEE_PERCENT = 2.0;    // 2%

    const FEE_MIN_BRL = 1.00;   // R$1 minimum for paid events

    const FREE_EVENT_FEE = 1.00;   // R$1 per registration on free events

    public function calculateFee(float $eventFee): float
    {
        if ($eventFee <= 0) {
            return self::FREE_EVENT_FEE;
        }

        $fee = $eventFee * (self::FEE_PERCENT / 100);

        return max($fee, self::FEE_MIN_BRL);
    }

    public function recordRegistrationFee(OrganizationEventRegistration $registration, float $eventFee): OrganizationBillingItem
    {
        $fee = $this->calculateFee($eventFee);
        $type = $eventFee > 0 ? 'registration_paid' : 'registration_free';

        return OrganizationBillingItem::create([
            'organization_id' => $registration->event->organization_id,
            'registration_id' => $registration->id,
            'billing_type' => $type,
            'fee_amount' => $fee,
        ]);
    }

    public function generateMonthlyInvoices(string $cycle = null): array
    {
        $cycle = $cycle ?? Carbon::now()->subMonth()->format('Y-m');

        $orgs = OrganizationBillingItem::whereNull('billing_cycle')
            ->groupBy('organization_id')
            ->pluck('organization_id');

        $results = [];

        foreach ($orgs as $orgId) {
            $items = OrganizationBillingItem::where('organization_id', $orgId)
                ->whereNull('billing_cycle')
                ->get();

            $total = $items->sum('fee_amount');

            $invoice = OrganizationBillingInvoice::firstOrCreate(
                ['organization_id' => $orgId, 'billing_cycle' => $cycle],
                [
                    'total_amount' => $total,
                    'status' => $total > 0 ? 'pending' : 'empty',
                    'due_date' => Carbon::createFromFormat('Y-m', $cycle)->startOfMonth()->addMonth()->startOfMonth(),
                ]
            );

            $items->each(fn ($item) => $item->update([
                'billing_cycle' => $cycle,
                'invoice_id' => $invoice->id,
            ]));

            $results[] = ['organization_id' => $orgId, 'invoice_id' => $invoice->id, 'total' => $total];
        }

        return $results;
    }

    public function blockOverdueOrgs(): array
    {
        $gracePeriodDays = 5;
        $blocked = [];

        $overdueInvoices = OrganizationBillingInvoice::where('status', 'failed')
            ->whereNull('organizations.billing_blocked_at')
            ->join('organizations', 'organizations.id', '=', 'organization_billing_invoices.organization_id')
            ->where('organization_billing_invoices.failed_at', '<=', now()->subDays($gracePeriodDays))
            ->select('organization_billing_invoices.*')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $invoice->organization->update(['billing_blocked_at' => now()]);
            $blocked[] = $invoice->organization_id;
        }

        return $blocked;
    }
}
