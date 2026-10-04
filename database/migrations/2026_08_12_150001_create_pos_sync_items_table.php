<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sync_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('pos_sync_batches')->cascadeOnDelete();
            $table->string('pos_item_id');
            $table->enum('action', ['create', 'update', 'deactivate']);
            $table->json('payload');
            $table->enum('status', ['pending', 'processed', 'failed', 'skipped'])
                ->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'pos_item_id']);
            $table->index('pos_item_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sync_items');
    }
};
