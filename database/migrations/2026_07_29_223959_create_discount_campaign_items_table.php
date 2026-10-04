<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_campaign_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['discount_campaign_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_campaign_items');
    }
};
