<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'cost', 'motorcycle_rate', 'truck_rate', 'description', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'motorcycle_rate' => 'decimal:2',
            'truck_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getBulkFee(?string $deliveryType = null): float
    {
        if ($deliveryType === 'motorcycle') {
            return (float) $this->motorcycle_rate;
        }
        return (float) $this->truck_rate;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
