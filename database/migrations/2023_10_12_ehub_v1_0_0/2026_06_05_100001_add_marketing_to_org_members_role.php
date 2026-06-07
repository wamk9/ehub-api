<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE organization_members MODIFY COLUMN role ENUM('owner','admin','financial','event_manager','marketing') NOT NULL");
        DB::statement('ALTER TABLE organization_invites MODIFY COLUMN role VARCHAR(50) NOT NULL');
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE organization_members MODIFY COLUMN role ENUM('owner','admin','financial','event_manager') NOT NULL");
    }
};
