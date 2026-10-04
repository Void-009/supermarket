<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id', 'user_id', 'message',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(OrderComplaint::class, 'complaint_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
