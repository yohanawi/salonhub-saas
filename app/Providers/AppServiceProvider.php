<?php

namespace App\Providers;

use Livewire\Livewire;
use App\Core\KTBootstrap;
use App\Models\Appointment;
use App\Models\User;
use App\Observers\AppointmentMessagingObserver;
use App\Services\Auth\TwoFactorService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use App\View\Composers\ReceiptBusinessDetailsComposer;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Update defaultStringLength
        Builder::defaultStringLength(191);

        KTBootstrap::init();

        Appointment::observe(AppointmentMessagingObserver::class);

        View::composer([
            'pages.apps.billing.invoices._receipt-card',
            'pages.apps.billing.invoices.receipt-thermal',
        ], ReceiptBusinessDetailsComposer::class);

        // Every sign-in path (password, social, "remember me") ends here, so each one must pass the
        // two-factor challenge before EnsureTwoFactorChallengePassed lets the user any further.
        Event::listen(Login::class, function (Login $event) {
            if ($event->guard === 'web' && $event->user instanceof User && $event->user->hasTwoFactorEnabled() && app()->bound('session')) {
                session()->put(TwoFactorService::SESSION_PENDING, true);
            }
        });

        if (app()->environment('production')) {
            Livewire::setUpdateRoute(function ($handle) {
                return Route::post('/starterkit/metronic/laravel/livewire/update', $handle);
            });
        }
    }
}
