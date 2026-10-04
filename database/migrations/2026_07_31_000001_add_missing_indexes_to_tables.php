<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            'orders' => ['user_id', 'processed_by', 'payment_status', 'payment_method', 'created_at'],
            'items' => ['name', 'slug', 'is_visible_online', 'selling_price', 'stock_status'],
            'item_barcodes' => ['item_id'],
            'order_items' => ['order_id', 'item_id'],
            'cart_items' => ['item_id', 'session_id'],
        ];

        foreach ($indexes as $table => $columns) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    if ($this->columnHasIndex($table->getTable(), $column)) {
                        continue;
                    }
                    $table->index($column);
                }
            });
        }
    }

    public function down(): void
    {
        $indexes = [
            'orders' => ['user_id', 'processed_by', 'payment_status', 'payment_method', 'created_at'],
            'items' => ['name', 'slug', 'is_visible_online', 'selling_price', 'stock_status'],
            'item_barcodes' => ['item_id'],
            'order_items' => ['order_id', 'item_id'],
            'cart_items' => ['item_id', 'session_id'],
        ];

        foreach ($indexes as $table => $columns) {
            foreach ($columns as $column) {
                if (Schema::hasIndex($table, $column)) {
                    Schema::table($table, function (Blueprint $table) use ($column) {
                        $table->dropIndex([$column]);
                    });
                }
            }
        }
    }

    private function columnHasIndex(string $table, string $column): bool
    {
        $stats = DB::table('information_schema.statistics')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->get();

        // A column is indexed if it appears in any index statistics row.
        // (Column names from information_schema may be upper-cased by PDO.)
        return $stats->isNotEmpty();
    }
};
