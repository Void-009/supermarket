<?php

namespace App\Services;

use App\Models\Order;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class BarcodeService
{
    public function generateBarcodeString(Order $order): string
    {
        $items = $order->items;
        $parts = ["MDS", "O{$order->id}"];

        foreach ($items as $item) {
            $posItemId = $item->item?->pos_item_id ?? $item->item_id;
            $parts[] = "{$posItemId}x{$item->quantity}";
        }

        return implode('-', $parts);
    }

    public function generateQrData(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            return [
                'id' => $item->item?->pos_item_id ?? $item->item_id,
                'qty' => $item->quantity,
            ];
        })->toArray();

        return [
            'store' => 'MDS',
            'order' => $order->id,
            'items' => $items,
        ];
    }

    public function generateQrCodeImage(Order $order): string
    {
        $qrData = $this->generateQrData($order);
        $jsonPayload = json_encode($qrData, JSON_UNESCAPED_SLASHES);

        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'eccLevel' => EccLevel::M,
            'scale' => 8,
            'returnResource' => false,
            'outputBase64' => false,
        ]);

        $qrcode = new QRCode($options);
        $rawImage = $qrcode->render($jsonPayload);

        return $rawImage;
    }
}
