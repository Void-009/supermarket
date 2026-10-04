<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('promo_price', 12, 2)->nullable()->after('selling_price');
            $table->timestamp('promo_start_at')->nullable()->after('promo_price');
            $table->timestamp('promo_end_at')->nullable()->after('promo_start_at');
            $table->boolean('pos_inactive')->default(false)->after('is_visible_online');
            $table->timestamp('last_synced_at')->nullable()->after('last_modified');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn([
                'promo_price',
                'promo_start_at',
                'promo_end_at',
                'pos_inactive',
                'last_synced_at',
            ]);
        });
    }
};
