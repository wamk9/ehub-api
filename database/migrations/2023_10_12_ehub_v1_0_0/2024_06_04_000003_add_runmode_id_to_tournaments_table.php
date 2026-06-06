<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->uuid('runmode_id')->nullable()->after('subscription_limit');
            $table->foreign('runmode_id')->references('id')->on('runmodes')->nullOnDelete()->cascadeOnUpdate();
        });
    }
    public function down(): void {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropForeign(['runmode_id']);
            $table->dropColumn('runmode_id');
        });
    }
};
