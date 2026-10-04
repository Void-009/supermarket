<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_barcodes', function (Blueprint $table) {
            $table->dropForeign('item_barcodes_item_id_foreign');
            $table->dropUnique('item_barcodes_item_id_barcode_type_unique');
            $table->unique(['item_id', 'barcode_value']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_barcodes', function (Blueprint $table) {
            $table->dropForeign('item_barcodes_item_id_foreign');
            $table->dropUnique('item_barcodes_item_id_barcode_value_unique');
            $table->unique(['item_id', 'barcode_type']);
            $table->foreign('item_id')->references('id')->on('items')->cascadeOnDelete();
        });
    }
};
