<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Link every variation item to its parent item.
     *
     * Variation items have a pos_item_id like "1070@4907", where the part
     * before the "@" is the parent item's pos_item_id. The sync command used
     * to import these rows without setting parent_item_number, which left
     * every variation unlinked from its parent.
     */
    public function up(): void
    {
        DB::table('items')
            ->where('pos_item_id', 'like', '%@%')
            ->where(fn ($q) => $q->whereNull('parent_item_number')->orWhere('parent_item_number', ''))
            ->update([
                'parent_item_number' => DB::raw("SUBSTRING_INDEX(pos_item_id, '@', 1)"),
            ]);
    }

    public function down(): void
    {
        // No safe reversal - parent_item_number values are derived from pos_item_id.
    }
};
