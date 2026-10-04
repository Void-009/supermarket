<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->string('facebook_url')->nullable()->after('whatsapp_number');
            $table->string('instagram_url')->nullable()->after('facebook_url');
            $table->string('x_url')->nullable()->after('instagram_url');
            $table->string('tiktok_url')->nullable()->after('x_url');
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn(['facebook_url', 'instagram_url', 'x_url', 'tiktok_url']);
        });
    }
};
