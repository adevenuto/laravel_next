<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Build password-reset URLs that point at the Next.js frontend
        // instead of a non-existent Laravel route.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontend = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));

            return rtrim($frontend, '/').'/reset-password?token='.$token
                .'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
