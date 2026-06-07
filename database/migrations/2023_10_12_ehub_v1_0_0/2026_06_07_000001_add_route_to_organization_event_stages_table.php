<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_event_stages', function (Blueprint $table) {
            $table->string('route')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('organization_event_stages', function (Blueprint $table) {
            $table->dropColumn('route');
        });
    }
};
