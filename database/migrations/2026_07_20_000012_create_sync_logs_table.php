<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->integer('rows_processed')->default(0);
            $table->integer('rows_updated')->default(0);
            $table->integer('rows_created')->default(0);
            $table->enum('status', ['success', 'failed', 'in_progress']);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};