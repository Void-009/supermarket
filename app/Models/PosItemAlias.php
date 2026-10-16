<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps a POS Item Id to the canonical item row it belongs to. See the
 * migration for why this exists (POS mints a new Item Id on every restock).
 */
class PosItemAlias extends Model
{
    protected $fillable = ['pos_item_id', 'item_id', 'reason'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
