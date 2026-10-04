<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'bank_code',
        'delivery_fee',
        'bank_transfer_instructions',
        'whatsapp_number',
        'facebook_url',
        'instagram_url',
        'x_url',
        'tiktok_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'delivery_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }
}