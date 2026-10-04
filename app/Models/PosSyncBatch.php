<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PosSyncBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_id',
        'source',
        'user_id',
        'ip_address',
        'status',
        'total_items',
        'processed_items',
        'failed_items',
        'processed_queue',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'total_items' => 'integer',
            'processed_items' => 'integer',
            'failed_items' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PosSyncItem::class, 'batch_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
