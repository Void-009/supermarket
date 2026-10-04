<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `sync_logs` MODIFY COLUMN `status` ENUM('in_progress', 'success', 'failed', 'cancelled', 'cancelling') NOT NULL DEFAULT 'in_progress'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `sync_logs` MODIFY COLUMN `status` ENUM('in_progress', 'success', 'failed') NOT NULL DEFAULT 'in_progress'");
    }
};
