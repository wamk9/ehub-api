<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('point_additional', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->tinyInteger('quantity')->unsigned();
            $table->uuid('point_category_id');
            $table->uuid('point_result_id');
            $table->uuid('point_protest_id')->nullable();
            $table->timestamps();
            $table->foreign('point_protest_id')->references('id')->on('point_protests')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('point_result_id')->references('id')->on('point_results')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('point_additional'); }
};
