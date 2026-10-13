<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!class_exists('Laravel\Jetstream\Jetstream')) {
            class_alias(\App\Support\JetstreamStub::class, 'Laravel\Jetstream\Jetstream');
        }
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('valiant-shade.api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => __('Too many requests. Please slow down and try again shortly.'),
                    ], 429);
                });
        });

        \Illuminate\Support\Facades\RateLimiter::for('valiant-shade.admin-mutations', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => __('Rate limit exceeded for administrative mutations. Please wait a moment.'),
                    ], 429);
                });
        });

        \Illuminate\Support\Facades\RateLimiter::for('valiant-shade.quest-mutations', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => __('Too many quest modifications. Please wait a moment.'),
                    ], 429);
                });
        });
    }
}
