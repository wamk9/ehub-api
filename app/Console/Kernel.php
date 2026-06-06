<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 1st of every month at 06:00 — generate and charge invoices
        $schedule->command('billing:generate-monthly')->monthlyOn(1, '06:00');

        // Daily at 08:00 — block orgs past 5-day grace period
        $schedule->command('billing:check-overdue')->dailyAt('08:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
