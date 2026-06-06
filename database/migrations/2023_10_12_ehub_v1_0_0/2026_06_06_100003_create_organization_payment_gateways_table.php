<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_payment_gateways', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('gateway', 20); // mercadopago | stripe_connect
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('gateway_user_id')->nullable();
            $table->string('public_key')->nullable(); // MP public key
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('organization_id', 'opg_org_fk')
                ->references('id')->on('organizations')->cascadeOnDelete();
            $table->unique(['organization_id', 'gateway'], 'opg_org_gateway_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_payment_gateways');
    }
};
