<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_references', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('num_order')->unsigned();
            $table->tinyInteger('point')->default(0);
            $table->uuid('point_round_id');
            $table->foreign('point_round_id')->references('id')->on('point_rounds')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_references');
    }
};
