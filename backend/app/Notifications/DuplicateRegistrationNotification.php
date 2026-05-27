<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DuplicateRegistrationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');

        return (new MailMessage)
            ->subject('Someone tried to sign up with your email')
            ->greeting('Hi '.$notifiable->first_name.',')
            ->line('Someone just tried to create a new account using your email address.')
            ->line('No new account was created — you already have one with us.')
            ->line("If that was you and you can't remember your password, you can reset it now.")
            ->action('Reset password', $frontend.'/forgot-password')
            ->line('If this was not you, you can safely ignore this email. No action is required.');
    }
}
