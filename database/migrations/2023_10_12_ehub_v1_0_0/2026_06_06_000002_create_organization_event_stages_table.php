<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_event_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_event_id')->constrained('organization_events')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('stage_type')->default('top_n'); // top_n, points, bracket
            $table->json('config')->nullable();
            $table->unsignedSmallInteger('stage_order')->default(0);
            $table->boolean('initialized')->default(false);
            $table->boolean('in_progress')->default(false);
            $table->boolean('finished')->default(false);
            $table->timestamp('start_at')->nullable();
            $table->timestamps();

            $table->index(['organization_event_id', 'stage_order'], 'oes_event_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_event_stages');
    }
};
