<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('point_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('point_event_id');
            $table->uuid('tournament_subscription_id');
            $table->uuid('point_reference_id')->nullable();
            $table->uuid('point_result_category_id');
            $table->foreign('tournament_subscription_id')->references('id')->on('tournaments_subscriptions')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('point_reference_id')->references('id')->on('point_references')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('point_result_category_id')->references('id')->on('point_result_categories')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('point_results'); }
};
