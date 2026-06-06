<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_event_stage_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_event_stage_id');
            $table->uuid('registration_id');
            $table->unsignedSmallInteger('position')->nullable();
            $table->decimal('score', 15, 4)->nullable();
            $table->boolean('qualified')->default(false);
            $table->json('result_data')->nullable();
            $table->timestamps();

            $table->foreign('organization_event_stage_id', 'oesr2_stage_fk')
                ->references('id')->on('organization_event_stages')->cascadeOnDelete();
            $table->foreign('registration_id', 'oesr2_reg_fk')
                ->references('id')->on('organization_event_registrations')->cascadeOnDelete();

            $table->unique(['organization_event_stage_id', 'registration_id'], 'oesr2_stage_reg_unique');
            $table->index(['organization_event_stage_id', 'position'], 'oesr2_stage_pos_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_event_stage_results');
    }
};
