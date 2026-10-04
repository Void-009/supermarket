<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_barcodes', function (Blueprint $table) {
            // Only drop the bare unique on barcode_value if it exists.
            // The composite unique (item_id, barcode_value) backs the item_id
            // foreign key and must NOT be dropped (MySQL error 1553).
            if (Schema::hasIndex('item_barcodes', 'item_barcodes_barcode_value_unique')) {
                $table->dropUnique('item_barcodes_barcode_value_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('item_barcodes', function (Blueprint $table) {
            $table->unique('barcode_value', 'item_barcodes_barcode_value_unique');
        });
    }
};
