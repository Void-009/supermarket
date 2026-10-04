<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Duplicate of 221146: guarded so it is a no-op when the table exists.
        if (Schema::hasTable('payment_settings')) {
            return;
        }

        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name')->default('Access Bank');
            $table->string('account_name')->default('M-Delight Singer Stores');
            $table->string('account_number')->default('1234567890');
            $table->string('bank_code')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(1500);
            $table->text('bank_transfer_instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};