<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL 5.7 doesn't support RENAME COLUMN — use CHANGE instead
        DB::statement('ALTER TABLE organization_events CHANGE `image` `cover_image` VARCHAR(255) NULL DEFAULT NULL');

        Schema::table('organization_events', function (Blueprint $table) {
            $table->string('logo_image')->nullable()->after('cover_image');
            $table->string('subcategory')->nullable()->after('runmode');
            $table->json('event_data')->nullable()->after('form_schema_id');
            $table->json('registration_form_template')->nullable()->after('event_data');
        });
    }

    public function down(): void
    {
        Schema::table('organization_events', function (Blueprint $table) {
            $table->dropColumn(['logo_image', 'subcategory', 'event_data', 'registration_form_template']);
        });

        DB::statement('ALTER TABLE organization_events CHANGE `cover_image` `image` VARCHAR(255) NULL DEFAULT NULL');
    }
};
