<?php

namespace App\Providers;

use App\ValiantShade\Listeners\LogAuthenticationActivity;
use Illuminate\Auth\Events\Failed;
use Spatie\Activitylog\Actions\LogActivityAction;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
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
        $authAudit = app(LogAuthenticationActivity::class);
        Event::listen(Login::class, [$authAudit, 'handleLogin']);
        Event::listen(Logout::class, [$authAudit, 'handleLogout']);
        Event::listen(Failed::class, [$authAudit, 'handleFailed']);

        LogActivityAction::beforeLogging(function ($activity): void {
            $user = auth()->user();
            if (! $user instanceof \App\Models\User) {
                return;
            }

            $props = $activity->properties?->toArray() ?? [];
            if (isset($props['causer_snapshot'])) {
                return;
            }

            $activity->properties = collect($props)->merge([
                'causer_snapshot' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'display_name' => $user->display_name ?? $user->name,
                    'role' => $user->role,
                ],
                'ip' => $props['ip'] ?? request()?->ip(),
                'user_agent' => $props['user_agent'] ?? substr((string) (request()?->userAgent() ?? ''), 0, 255),
            ]);
        });

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
