<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Variation availability is derived from the parent item at query time
     * (see Item::effectiveQuantity), so a child must never be hidden simply
     * because the POS reports its own quantity as 0. Make every non-inactive
     * variation listed; the storefront then decides buyability from the
     * parent's stock. POS-inactive variations stay hidden.
     */
    public function up(): void
    {
        DB::table('items')
            ->whereNotNull('parent_item_number')
            ->where('parent_item_number', '!=', '')
            ->update([
                'is_visible_online' => DB::raw('CASE WHEN pos_inactive = 1 THEN 0 ELSE 1 END'),
            ]);
    }

    public function down(): void
    {
        // No safe reversal - visibility is derived from the parent item.
    }
};
