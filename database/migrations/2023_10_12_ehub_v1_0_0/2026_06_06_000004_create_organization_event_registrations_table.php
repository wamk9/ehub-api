<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_event_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_event_id');
            $table->uuid('user_id');
            $table->uuid('team_id')->nullable();
            $table->json('form_data')->nullable();
            // free | pending | confirmed | rejected
            $table->string('payment_status', 20)->default('free');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_event_id', 'oer_event_fk')
                ->references('id')->on('organization_events')->cascadeOnDelete();
            $table->foreign('user_id', 'oer_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['organization_event_id', 'user_id'], 'oer_event_user_unique');
            $table->index(['organization_event_id', 'payment_status'], 'oer_event_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_event_registrations');
    }
};
