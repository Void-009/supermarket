<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Tells admin-panel staff that the customer amended an order that may already
 * be in progress, so whoever is packing it re-reads the basket and total.
 */
class CustomerUpdatedOrder extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $changes
     */
    public function __construct(
        public Order $order,
        public array $changes = []
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

        $detail = $this->changes
            ? ' Changed: ' . implode(', ', $this->changes) . '.'
            : '';

        return [
            'title' => "Order #{$this->order->id} was changed by the customer",
            'body' => "{$this->order->customer_name} updated their order: {$itemCount} items, "
                . '₦' . number_format((float) $this->order->total_amount, 2) . ".{$detail}",
            'data' => [
                'url' => route('admin.orders.show', $this->order->id),
            ],
            'tag' => "order-updated-{$this->order->id}",
            'renotify' => true,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Review order',
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
