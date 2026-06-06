<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('category_runmode', function (Blueprint $table) {
            $table->uuid('category_id');
            $table->uuid('runmode_id');
            $table->primary(['category_id', 'runmode_id']);
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('runmode_id')->references('id')->on('runmodes')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
    public function down(): void { Schema::dropIfExists('category_runmode'); }
};
