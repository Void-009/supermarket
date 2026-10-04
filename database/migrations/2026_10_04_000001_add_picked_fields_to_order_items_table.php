<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->timestamp('picked_at')->nullable()->after('price_at_order');
            $table->foreignId('picked_by')->nullable()->after('picked_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['picked_by']);
            $table->dropColumn(['picked_at', 'picked_by']);
        });
    }
};
