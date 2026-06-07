<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->float('price')->default(0);
            $table->string('recurrence')->default('monthly');
            $table->uuid('currency_id');
            $table->integer('max_tournament')->unsigned()->default(0);
            $table->integer('max_event')->unsigned()->default(0);
            $table->boolean('recurring_payment')->default(false);
            $table->boolean('protests')->default(false);
            $table->foreign('currency_id')->references('id')->on('currencies')->restrictOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
