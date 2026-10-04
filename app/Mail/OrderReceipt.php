<?php

namespace App\Mail;

use App\Models\Order;
use App\Services\BarcodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderReceipt extends Mailable
{
    use Queueable, SerializesModels;

    private ?string $barcodeString = null;
    private ?string $barcodeBase64 = null;

    public function __construct(
        public Order $order,
    ) {}

    private function generateBarcodeData(): void
    {
        if ($this->barcodeString !== null) {
            return;
        }
        $barcodeService = app(BarcodeService::class);
        $this->barcodeString = $barcodeService->generateBarcodeString($this->order);
        $barcodeImage = $barcodeService->generateQrCodeImage($this->order);
        $this->barcodeBase64 = base64_encode($barcodeImage);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your M-Delight order #{$this->order->id} is confirmed",
        );
    }

    public function content(): Content
    {
        $this->generateBarcodeData();

        return new Content(
            view: 'emails.orders.receipt-online',
            with: [
                'order' => $this->order,
                'barcodeString' => $this->barcodeString,
                'barcodeBase64' => $this->barcodeBase64,
            ],
        );
    }

    /**
     * The attached PDF is the customer-facing confirmation, not the thermal
     * roll. The 80mm layout is still rendered on demand for the printer by
     * Admin\OrderController::receipt() / ::downloadReceipt().
     */
    public function attachments(): array
    {
        $this->generateBarcodeData();

        $pdf = Pdf::loadView('emails.orders.receipt-online', [
            'order' => $this->order,
            'barcodeString' => $this->barcodeString,
            'barcodeBase64' => $this->barcodeBase64,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), "order-confirmation-{$this->order->id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
