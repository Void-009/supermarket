<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * owned_by_pos: who is allowed to hide this row.
     *
     * true  - the till created it, so an export that no longer lists its
     *         POS Item Id proves it is gone and reconcile may tombstone it.
     * false - it was created on the website, so the till has no opinion
     *         about it and reconcile must leave it alone, even when the
     *         row carries a POS Item Id that the export does not list.
     *
     * Backfilled from last_synced_at so the deploy changes no row's state:
     * a row that has ever been synced is the only kind that ever had a
     * last_synced_at, which is exactly the rule reconcile used before.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->boolean('owned_by_pos')->default(false)->after('hidden_by_admin');
        });

        DB::table('items')
            ->whereNotNull('last_synced_at')
            ->update(['owned_by_pos' => true]);
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('owned_by_pos');
        });
    }
};
