<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('users_hierarchies', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('hierarchy_id');
            $table->uuid('league_id');
            $table->primary(['user_id', 'hierarchy_id', 'league_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('hierarchy_id')->references('id')->on('hierarchies')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('league_id')->references('id')->on('leagues')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('users_hierarchies'); }
};
