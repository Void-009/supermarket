<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends BaseVerifyEmail
{
    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);
        $expiry = (int) config('auth.verification.expire', 60);

        return (new MailMessage)
            ->subject('Verify your email — M-DELIGHT Singer Stores')
            ->greeting('Hello ' . ($notifiable->name ?? 'there') . '!')
            ->line('Thank you for creating an account with M-DELIGHT Singer Stores.')
            ->line('Please click the button below to verify your email address and activate your account.')
            ->line('This verification link will expire in ' . $expiry . ' minutes.')
            ->action('Verify Email Address', $url)
            ->line('If you did not create this account, no further action is required.');
    }
}
