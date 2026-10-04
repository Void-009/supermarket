<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every variation item a sellable stock baseline.
     *
     * Variation rows come from the POS export with quantity 0 (the export does
     * not report per-variation stock). They are normally stocked manually from
     * the admin variations page. This migration seeds them so they are
     * immediately buyable in the online shop: quantity at least 5, visible
     * online, and marked in stock.
     */
    public function up(): void
    {
        DB::table('items')
            ->whereNotNull('parent_item_number')
            ->where('parent_item_number', '!=', '')
            ->update([
                'quantity' => DB::raw('GREATEST(quantity, 5)'),
                'stock_status' => 'in_stock',
                'is_visible_online' => true,
            ]);
    }

    public function down(): void
    {
        // No safe reversal - quantities are managed manually from the admin page.
    }
};
