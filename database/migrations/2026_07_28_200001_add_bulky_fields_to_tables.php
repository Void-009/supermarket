<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('is_bulky')->default(false)->after('is_visible_online');
        });

        Schema::table('delivery_locations', function (Blueprint $table) {
            $table->decimal('motorcycle_rate', 12, 2)->default(0)->after('cost');
            $table->decimal('truck_rate', 12, 2)->default(0)->after('motorcycle_rate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_bulk_order')->default(false)->after('delivery_fee');
            $table->enum('delivery_fee_status', ['standard', 'pending_agent', 'confirmed'])->default('standard')->after('is_bulk_order');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['is_bulk_order', 'delivery_fee_status']);
        });

        Schema::table('delivery_locations', function (Blueprint $table) {
            $table->dropColumn(['motorcycle_rate', 'truck_rate']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('is_bulky');
        });
    }
};
