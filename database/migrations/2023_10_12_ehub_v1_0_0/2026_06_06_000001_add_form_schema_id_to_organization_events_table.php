<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('organization_events', 'form_schema_id')) {
            Schema::table('organization_events', function (Blueprint $table) {
                $table->uuid('form_schema_id')->nullable()->after('runmode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('organization_events', 'form_schema_id')) {
            Schema::table('organization_events', function (Blueprint $table) {
                $table->dropColumn('form_schema_id');
            });
        }
    }
};
