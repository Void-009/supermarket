<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBarcode extends Model
{
    use HasFactory;

    /**
     * The item_barcodes table has no created_at/updated_at columns.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'barcode_type',
        'barcode_value',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (ItemBarcode $itemBarcode) {
            if (!empty($itemBarcode->barcode_value)) {
                $itemBarcode->barcode_value = strtolower($itemBarcode->barcode_value);
            }
        });
    }

    /**
     * Get the item that owns the barcode.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
