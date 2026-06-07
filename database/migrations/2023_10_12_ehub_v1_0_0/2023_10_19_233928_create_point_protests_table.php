<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_protests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('subscription_from');
            $table->uuid('subscription_to');
            $table->text('description')->nullable();
            $table->text('judgment')->nullable();
            $table->uuid('point_event_id');
            $table->timestamps();
            $table->foreign('subscription_from')->references('id')->on('tournaments_subscriptions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('subscription_to')->references('id')->on('tournaments_subscriptions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('point_event_id')->references('id')->on('point_events')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_protests');
    }
};
