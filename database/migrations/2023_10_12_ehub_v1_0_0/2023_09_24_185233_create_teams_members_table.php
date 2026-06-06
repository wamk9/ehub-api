<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams_members', function (Blueprint $table) {
            $table->uuid('team_id');
            $table->uuid('member_id');
            $table->boolean('is_admin')->default(false);

            $table->primary(['team_id', 'member_id']);
            $table->foreign('team_id')->references('id')->on('teams');
            $table->foreign('member_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams_members');
    }
};
