<?php

namespace App\Providers;

use App\Models\Items;
use App\Observers\ItemsObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Items::observe(ItemsObserver::class);

        // Link reset password mengarah ke halaman FE (bukan backend).
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return config('app.frontend_url')."/reset-password?token={$token}&email={$email}";
        });
    }
}
