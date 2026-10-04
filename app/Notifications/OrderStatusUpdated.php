<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable->pushSubscriptions()->exists() ? [PushChannel::class] : [];
    }

    public function toPush(object $notifiable): array
    {
        $statusLabels = [
            'new' => 'New',
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'ready' => 'Ready for Delivery',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
        ];

        $label = $statusLabels[$this->newStatus] ?? ucfirst($this->newStatus);

        return [
            'title' => "Order #{$this->order->id} — {$label}",
            'body' => "Your order of ₦" . number_format($this->order->total_amount, 2) . " is now: {$label}.",
            'data' => [
                'url' => route('customer.orders.show', $this->order->id),
            ],
            'tag' => "order-{$this->order->id}",
            'renotify' => true,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'View Order',
                ],
            ],
        ];
    }

    public function viaQueues(): array
    {
        return [
            PushChannel::class => 'push-notifications',
        ];
    }
}
