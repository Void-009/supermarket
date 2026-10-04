<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->isMysql()) {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('new', 'processing', 'paid', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'new'");
        }
    }

    public function down(): void
    {
        if ($this->isMysql()) {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('new', 'processing', 'delivered', 'cancelled') NOT NULL DEFAULT 'new'");
        }
    }

    private function isMysql(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};
