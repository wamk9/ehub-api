<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('tournaments_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('team_id')->nullable();
            $table->uuid('tournament_id');
            $table->uuid('payment_status_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate();
            $table->foreign('team_id')->references('id')->on('teams')->cascadeOnUpdate();
            $table->foreign('tournament_id')->references('id')->on('tournaments')->cascadeOnUpdate();
            $table->foreign('payment_status_id')->references('id')->on('payments_status')->cascadeOnUpdate();
        });
    }
    public function down(): void { Schema::dropIfExists('tournaments_subscriptions'); }
};
