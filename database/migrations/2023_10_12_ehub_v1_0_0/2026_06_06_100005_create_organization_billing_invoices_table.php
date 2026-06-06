<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_billing_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('billing_cycle', 7); // YYYY-MM
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            // pending | paid | failed | waived | empty
            $table->string('status', 20)->default('pending');
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_id', 'obinv_org_fk')
                ->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'billing_cycle'], 'obinv_org_cycle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_billing_invoices');
    }
};
