<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_event_registrations', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('payment_status');
            $table->string('gateway_payment_id')->nullable()->after('gateway');
            $table->string('gateway_preference_id')->nullable()->after('gateway_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('organization_event_registrations', function (Blueprint $table) {
            $table->dropColumn(['gateway', 'gateway_payment_id', 'gateway_preference_id']);
        });
    }
};
