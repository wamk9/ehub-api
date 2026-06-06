<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_event_stage_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_event_stage_id');
            $table->string('name');
            $table->json('config')->nullable();
            $table->unsignedSmallInteger('round_order')->default(0);
            $table->boolean('initialized')->default(false);
            $table->boolean('in_progress')->default(false);
            $table->boolean('finished')->default(false);
            $table->timestamp('start_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_event_stage_id', 'oesr_stage_fk')
                ->references('id')->on('organization_event_stages')->cascadeOnDelete();
            $table->index(['organization_event_stage_id', 'round_order'], 'oesr_stage_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_event_stage_rounds');
    }
};
