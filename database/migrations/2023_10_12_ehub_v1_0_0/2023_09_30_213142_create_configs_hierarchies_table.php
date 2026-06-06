<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('configs_hierarchies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('league_id');
            $table->uuid('hierarchy_id');
            $table->boolean('create_league_tournaments')->default(false);
            $table->boolean('delete_league_tournaments')->default(false);
            $table->boolean('edit_league_hierarchies')->default(false);
            $table->boolean('edit_league_info')->default(false);
            $table->boolean('edit_league_protests')->default(false);
            $table->boolean('edit_league_tournaments')->default(false);
            $table->boolean('view_menu')->default(false);
            $table->foreign('league_id')->references('league_id')->on('hierarchies')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('hierarchy_id')->references('id')->on('hierarchies')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }
    public function down(): void { Schema::dropIfExists('configs_hierarchies'); }
};
