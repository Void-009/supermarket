<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSyncAgentStatus extends Model
{
    protected $fillable = ['source', 'version', 'last_seen_at'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }
}
