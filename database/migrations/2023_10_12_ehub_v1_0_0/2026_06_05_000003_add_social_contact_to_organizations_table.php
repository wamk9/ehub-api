<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('organizations', function (Blueprint $table) {
            $table->longText('about')->nullable()->after('description');
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('x_twitter')->nullable();
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_email')->nullable();
        });
    }
    public function down(): void {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['about', 'instagram', 'facebook', 'x_twitter', 'website', 'phone', 'contact_email']);
        });
    }
};
