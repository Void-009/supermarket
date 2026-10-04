<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('sync_id')->unique();
            $table->string('source', 100)->default('pos-agent');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'duplicate'])
                ->default('pending');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('processed_items')->default(0);
            $table->unsignedInteger('failed_items')->default(0);
            $table->string('processed_queue', 50)->default('pos-sync');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sync_batches');
    }
};
