<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Browser push telling admin-panel staff that a new order has arrived.
 *
 * Recipients are resolved by the caller (User::orderAlertRecipients) so this
 * class stays a plain payload builder, matching OrderStatusUpdated.
 */
class NewOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->pushSubscriptions()->exists() ? [PushChannel::class] : [];
    }

    public function toPush(object $notifiable): array
    {
        $itemCount = (int) $this->order->items()->sum('quantity');

        $paymentLabel = $this->order->payment_method === 'bank_transfer'
            ? 'Bank transfer'
            : 'Cash on delivery';

        return [
            'title' => "New order #{$this->order->id}",
            'body' => "{$this->order->customer_name} placed a {$itemCount} item order for "
                . '₦' . number_format((float) $this->order->total_amount, 2)
                . " ({$paymentLabel}).",
            'data' => [
                'url' => route('admin.orders.show', $this->order->id),
            ],
            // Tagged per order so repeat alerts for one order collapse into a
            // single notification instead of stacking up in the tray.
            'tag' => "new-order-{$this->order->id}",
            'renotify' => true,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'View order',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            PushChannel::class => 'push-notifications',
        ];
    }
}
