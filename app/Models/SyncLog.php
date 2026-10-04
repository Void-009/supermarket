<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'mode',
        'rows_processed',
        'rows_updated',
        'rows_created',
        'rows_failed',
        'rows_total',
        'status',
        'error_message',
        'error_details',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'rows_processed' => 'integer',
            'rows_updated' => 'integer',
            'rows_created' => 'integer',
            'rows_failed' => 'integer',
            'rows_total' => 'integer',
            'error_details' => 'array',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->rows_total <= 0) return 0;
        return (int) min(100, round(($this->rows_processed / $this->rows_total) * 100));
    }
}
