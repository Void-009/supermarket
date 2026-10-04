<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->enum('barcode_type', ['item_number', 'product_id', 'display_name']);
            $table->string('barcode_value');

            $table->unique(['item_id', 'barcode_type']);
            $table->index('barcode_value');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_barcodes');
    }
};