<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_billing_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('registration_id')->nullable();
            // registration_paid | registration_free
            $table->string('billing_type', 30);
            $table->decimal('fee_amount', 10, 2);
            $table->string('billing_cycle', 7)->nullable(); // YYYY-MM
            $table->uuid('invoice_id')->nullable();
            $table->timestamps();

            $table->foreign('organization_id', 'obi_org_fk')
                ->references('id')->on('organizations')->cascadeOnDelete();
            $table->foreign('registration_id', 'obi_reg_fk')
                ->references('id')->on('organization_event_registrations')->nullOnDelete();
            $table->index(['organization_id', 'billing_cycle'], 'obi_org_cycle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_billing_items');
    }
};
