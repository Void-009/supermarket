<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * pos_missing_since: set when a reconcile pass proves the POS Item Id is
     * gone from the POS inventory. The row is tombstoned (hidden, inactive)
     * but kept so a later re-creation with a fresh POS Item Id can be
     * fingerprint-matched and revived instead of duplicating.
     *
     * hidden_by_admin: a durable manual off-switch. The POS sync never
     * touches it, so hiding an item in the admin UI survives every future
     * sync (unlike is_visible_online, which the sync recalculates).
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->timestamp('pos_missing_since')->nullable()->after('last_synced_at');
            $table->boolean('hidden_by_admin')->default(false)->after('pos_missing_since');
            $table->index(['parent_item_number', 'pos_missing_since']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['parent_item_number', 'pos_missing_since']);
            $table->dropColumn(['pos_missing_since', 'hidden_by_admin']);
        });
    }
};
