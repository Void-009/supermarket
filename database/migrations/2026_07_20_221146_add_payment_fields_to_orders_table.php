<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash_on_delivery', 'bank_transfer'])->default('cash_on_delivery')->after('delivery_address');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('payment_method');
            $table->enum('payment_status', ['pending', 'verified', 'failed', 'refunded'])->default('pending')->after('delivery_fee');
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->string('bank_receipt_path')->nullable()->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'delivery_fee', 'payment_status', 'payment_reference', 'bank_receipt_path']);
        });
    }
};