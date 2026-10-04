<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('pos_item_id')->unique();
            $table->string('parent_item_number')->nullable();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('cost_price', 12, 2);
            $table->decimal('selling_price', 12, 2);
            $table->integer('quantity')->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_visible_online')->default(true);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'low_stock'])->default('in_stock');
            $table->timestamp('last_modified')->nullable();
            $table->timestamps();

            $table->index('parent_item_number');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
