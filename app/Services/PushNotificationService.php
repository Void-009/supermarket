<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    public function sendToUser(User $user, array $payload): void
    {
        $subscriptions = $user->pushSubscriptions;

        foreach ($subscriptions as $sub) {
            $this->sendToSubscription($sub, $payload);
        }

        $this->flush();
    }

    public function sendToAllUsers(array $payload): void
    {
        $subscriptions = PushSubscription::with('user')->get();

        foreach ($subscriptions as $sub) {
            $this->sendToSubscription($sub, $payload);
        }

        $this->flush();
    }

    protected function sendToSubscription(PushSubscription $sub, array $payload): void
    {
        $subscription = Subscription::create([
            'endpoint' => $sub->endpoint,
            'authToken' => $sub->auth_key,
            'publicKey' => $sub->p256dh_key,
            'contentEncoding' => 'aesgcm',
        ]);

        $this->webPush->queueNotification(
            $subscription,
            json_encode($payload)
        );
    }

    protected function flush(): void
    {
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $endpoint = $report->getEndpoint();
                PushSubscription::where('endpoint', $endpoint)->delete();
            }
        }
    }

    public function sendNotification(
        User $user,
        string $title,
        string $body,
        string $url = '/',
        ?string $tag = null,
        ?string $image = null,
        array $actions = []
    ): void {
        $payload = [
            'title' => $title,
            'body' => $body,
            'data' => ['url' => $url],
            'tag' => $tag ?? 'default',
            'renotify' => $tag !== null,
        ];

        if ($image) {
            $payload['image'] = $image;
        }

        if (!empty($actions)) {
            $payload['actions'] = $actions;
        }

        $this->sendToUser($user, $payload);
    }

    public function sendBroadcast(
        string $title,
        string $body,
        string $url = '/',
        ?string $tag = null,
        ?string $image = null
    ): void {
        $payload = [
            'title' => $title,
            'body' => $body,
            'data' => ['url' => $url],
            'tag' => $tag ?? 'broadcast',
            'renotify' => $tag !== null,
        ];

        if ($image) {
            $payload['image'] = $image;
        }

        $this->sendToAllUsers($payload);
    }
}
