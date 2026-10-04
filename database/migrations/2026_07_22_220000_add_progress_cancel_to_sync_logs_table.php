<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            $table->integer('rows_total')->default(0)->after('rows_failed');
            $table->timestamp('cancelled_at')->nullable()->after('rows_total');
            $table->json('error_details')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('sync_logs', function (Blueprint $table) {
            $table->dropColumn(['rows_total', 'cancelled_at', 'error_details']);
        });
    }
};
