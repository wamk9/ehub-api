<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_form_schemas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignUuid('subcategory_id')->nullable()->constrained('subcategories')->nullOnDelete();
            $table->foreignUuid('runmode_id')->constrained('runmodes')->cascadeOnDelete();
            $table->json('form_json');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['category_id', 'subcategory_id', 'runmode_id', 'created_at'], 'efs_cat_sub_run_created_idx');
        });

        Schema::table('organization_events', function (Blueprint $table) {
            $table->foreignUuid('form_schema_id')->nullable()->constrained('event_form_schemas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('organization_events', function (Blueprint $table) {
            $table->dropForeign(['form_schema_id']);
            $table->dropColumn('form_schema_id');
        });

        Schema::dropIfExists('event_form_schemas');
    }
};
