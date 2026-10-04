<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemTranslation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'locale',
        'name',
        'description',
    ];

    /**
     * Get the item that owns the translation.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
