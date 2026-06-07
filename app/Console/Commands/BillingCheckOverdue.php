<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class BillingCheckOverdue extends Command
{
    protected $signature = 'billing:check-overdue';

    protected $description = 'Block organizations with overdue invoices past the 5-day grace period';

    public function handle(BillingService $billing): int
    {
        $blocked = $billing->blockOverdueOrgs();

        if (empty($blocked)) {
            $this->info('No organizations to block.');

            return 0;
        }

        $this->warn('Blocked '.count($blocked).' organization(s): '.implode(', ', $blocked));

        return 0;
    }
}
