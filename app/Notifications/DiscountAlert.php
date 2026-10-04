<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DiscountAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public ?string $url = '/shop',
        public ?string $image = null,
        public ?string $tag = null
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable->pushSubscriptions()->exists() ? [PushChannel::class] : [];
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'data' => [
                'url' => $this->url,
            ],
            'tag' => $this->tag ?? 'discount',
            'image' => $this->image,
            'actions' => [
                [
                    'action' => 'view',
                    'title' => 'Shop Now',
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
