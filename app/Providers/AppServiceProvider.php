<?php

namespace App\Providers;

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
        // Intentionally disabled to avoid duplicate booking emails being triggered
        // from multiple sources (controller + observer + model callbacks).
        // Booking notifications are now handled in explicit request/action flows.
    }
}
