<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('route');
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('fee', 10, 2)->default(0);
            $table->string('currency', 10)->default('brl');
            $table->integer('max_registrations')->nullable();
            $table->boolean('initialized')->default(false);
            $table->boolean('finished')->default(false);
            $table->timestamp('start_at')->nullable();
            $table->string('category')->nullable();
            $table->string('runmode')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'route']);
            $table->index(['organization_id', 'finished']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_events');
    }
};
