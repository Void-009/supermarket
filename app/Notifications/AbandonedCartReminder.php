<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AbandonedCartReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $itemCount,
        public float $totalAmount
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable->pushSubscriptions()->exists() ? [PushChannel::class] : [];
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => '🛒 Complete Your Order!',
            'body' => "You have {$this->itemCount} item(s) in your cart totaling ₦" 
                . number_format($this->totalAmount, 2) 
                . ". Don't miss out — complete your order now!",
            'data' => [
                'url' => route('cart.index'),
            ],
            'tag' => 'abandoned-cart',
            'renotify' => true,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'View Cart',
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
