<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('point_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('point_event_id');
            $table->timestamps();
            $table->foreign('point_event_id')->references('id')->on('point_events')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('point_rounds'); }
};
