<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'item_id',
        'quantity',
        'price_at_order',
        'picked_at',
        'picked_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_at_order' => 'decimal:2',
            'quantity' => 'integer',
            'picked_at' => 'datetime',
        ];
    }

    /**
     * Get the order that owns the order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the item for the order item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function pickedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'picked_by');
    }

    public function isPicked(): bool
    {
        return $this->picked_at !== null;
    }
}
