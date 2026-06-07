<?php

namespace App\Console\Commands;

use App\Models\Organization\OrganizationEvent;
use Illuminate\Console\Command;

class InitializeStartedEvents extends Command
{
    protected $signature = 'events:initialize-started';

    protected $description = 'Auto-initialize events whose start_at has passed';

    public function handle(): void
    {
        OrganizationEvent::where('initialized', false)
            ->where('finished', false)
            ->whereNotNull('start_at')
            ->where('start_at', '<=', now())
            ->update(['initialized' => true]);
    }
}
