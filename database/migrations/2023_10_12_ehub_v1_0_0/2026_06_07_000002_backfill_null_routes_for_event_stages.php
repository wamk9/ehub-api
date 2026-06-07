<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $stages = DB::table('organization_event_stages')
            ->whereNull('route')
            ->get();

        foreach ($stages as $stage) {
            $base = Str::slug($stage->name ?? 'stage', '-') ?: 'stage';
            $slug = $base;
            $counter = 2;

            while (DB::table('organization_event_stages')
                ->where('organization_event_id', $stage->organization_event_id)
                ->where('route', $slug)
                ->where('id', '!=', $stage->id)
                ->exists()
            ) {
                $slug = $base.'-'.$counter++;
            }

            DB::table('organization_event_stages')
                ->where('id', $stage->id)
                ->update(['route' => $slug]);
        }
    }

    public function down(): void {}
};
