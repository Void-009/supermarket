<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maps a POS Item Id to the canonical item row it belongs to.
     *
     * The POS deletes a variation on runout and mints a brand new Item Id on
     * restock. When the new id is fingerprint-matched to an existing row,
     * an alias is recorded so every future payload for that id (create,
     * update or deactivate) is applied to the canonical row. Without this,
     * the new id would recreate a duplicate the next time it syncs.
     */
    public function up(): void
    {
        Schema::create('pos_item_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('pos_item_id')->unique();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('reason')->default('restored');
            $table->timestamps();

            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_item_aliases');
    }
};
