<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlideshowImage extends Model
{
    protected $fillable = [
        'path',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
