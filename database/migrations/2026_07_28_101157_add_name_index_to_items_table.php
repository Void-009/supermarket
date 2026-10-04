<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasIndex('items', 'items_name_index')) {
                $table->index('name');
            }

            if (! Schema::hasIndex('items', 'items_display_name_index')) {
                $table->index('display_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['display_name']);
        });
    }
};
