<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('currencies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('country_iso_code', 2);
            $table->string('currency_iso_code', 3);
        });
    }
    public function down(): void { Schema::dropIfExists('currencies'); }
};
