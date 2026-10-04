<?php

namespace App\Notifications;

use App\Services\PushNotificationService;
use Illuminate\Notifications\Notification;

class PushChannel
{
    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toPush')) {
            return;
        }

        $payload = $notification->toPush($notifiable);

        if (empty($payload['title']) && empty($payload['body'])) {
            return;
        }

        $this->pushService->sendToUser($notifiable, $payload);
    }
}
