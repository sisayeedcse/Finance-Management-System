<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'planning' to the projects.status enum
        DB::statement("ALTER TABLE `projects` MODIFY `status` ENUM('active','planning','completed','cancelled') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        // Revert to original enum (remove 'planning')
        DB::statement("ALTER TABLE `projects` MODIFY `status` ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active'");
    }
};
