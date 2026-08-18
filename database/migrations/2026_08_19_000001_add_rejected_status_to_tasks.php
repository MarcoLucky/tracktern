<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tasks') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE tasks MODIFY status ENUM('pending', 'approved', 'needs_revision', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (!Schema::hasTable('tasks') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('tasks')->where('status', 'rejected')->update(['status' => 'needs_revision']);
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('pending', 'approved', 'needs_revision') NOT NULL DEFAULT 'pending'");
    }
};
