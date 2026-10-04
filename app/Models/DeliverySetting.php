<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, $value): static
    {
        return static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getBulkQuantityThreshold(): int
    {
        return (int) static::get('bulk_quantity_threshold', 10);
    }

    public static function showDeliveryFeeSelector(): bool
    {
        return static::get('show_delivery_fee_selector', '1') === '1';
    }

    public static function getContactAgentMessage(): string
    {
        return static::get('contact_agent_message', 'Contact our delivery agent to confirm the delivery fee for your order. Our team will reach out to finalize the cost based on your location and order size.');
    }

    public static function getContactAgentPhone(): string
    {
        return static::getContactAgentPhones()[0] ?? '';
    }

    public static function getContactAgentPhones(): array
    {
        $list = static::decodeNumbers(static::get('contact_agent_phones', ''));

        if (empty($list)) {
            $single = static::get('contact_agent_phone', '');
            if ($single !== '') {
                $list = [$single];
            }
        }

        return static::normalizeNumbers($list);
    }

    public static function getContactAgentWhatsapp(): string
    {
        return static::getContactAgentWhatsapps()[0] ?? '';
    }

    public static function getContactAgentWhatsapps(): array
    {
        $list = static::decodeNumbers(static::get('contact_agent_whatsapps', ''));

        if (empty($list)) {
            $single = static::get('contact_agent_whatsapp', '');
            if ($single !== '') {
                $list = [$single];
            }
        }

        if (empty($list)) {
            $paymentSettings = PaymentSettings::getActive();
            if ($paymentSettings->whatsapp_number ?? '') {
                $list = [$paymentSettings->whatsapp_number];
            }
        }

        return static::normalizeNumbers($list);
    }

    private static function decodeNumbers(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function normalizeNumbers(array $list): array
    {
        $out = [];
        foreach ($list as $number) {
            $number = preg_replace('/[^0-9+]/', '', trim((string) $number));
            if ($number === '' || in_array($number, $out, true)) {
                continue;
            }
            $out[] = $number;
        }

        return $out;
    }
}
