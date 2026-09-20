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
        if (!class_exists('Laravel\Jetstream\Jetstream')) {
            class_alias(\App\Support\JetstreamStub::class, 'Laravel\Jetstream\Jetstream');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
