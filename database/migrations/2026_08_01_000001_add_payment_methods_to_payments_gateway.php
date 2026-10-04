<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->isMysql()) {
            DB::statement("ALTER TABLE payments MODIFY gateway ENUM('paystack', 'flutterwave', 'cash', 'cash_on_delivery', 'bank_transfer') NOT NULL");
        }
    }

    public function down(): void
    {
        if ($this->isMysql()) {
            DB::statement("ALTER TABLE payments MODIFY gateway ENUM('paystack', 'flutterwave', 'cash') NOT NULL");
        }
    }

    private function isMysql(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};
