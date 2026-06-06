<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('hierarchies', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('editable')->default(true);
            $table->uuid('league_id');
            $table->primary(['id', 'league_id']);
            $table->foreign('league_id')->references('id')->on('leagues')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('hierarchies'); }
};
