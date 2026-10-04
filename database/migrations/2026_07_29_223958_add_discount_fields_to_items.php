<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('selling_price');
            $table->boolean('discount_is_active')->default(false)->after('discount_percentage');
            $table->timestamp('discount_starts_at')->nullable()->after('discount_is_active');
            $table->timestamp('discount_ends_at')->nullable()->after('discount_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['discount_percentage', 'discount_is_active', 'discount_starts_at', 'discount_ends_at']);
        });
    }
};
