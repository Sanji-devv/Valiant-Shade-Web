<?php

namespace App\Providers;

use App\Support\PublicSiteUrls;
use App\ValiantShade\Listeners\LogAuthenticationActivity;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Failed;
use Spatie\Activitylog\Actions\LogActivityAction;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use App\ValiantShade\Health\Checks\CpuLoadCheck;

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
        $this->configureProductionHttps();
        $this->configurePublicPortalUrlRoot();
        $this->configureLocalSessionForGazetteSubdomain();

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

        \Illuminate\Support\Facades\RateLimiter::for('public.news.submissions', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(6)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => __('Too many rumor submissions. Please wait before sending another.'),
                    ], 429);
                });
        });

        \Illuminate\Support\Facades\RateLimiter::for('public.npc.submissions', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(6)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => __('Too many NPC submissions. Please wait before sending another.'),
                    ], 429);
                });
        });

        \Illuminate\Support\Facades\RateLimiter::for('public.god.submissions', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(6)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'success' => false,
                        'message' => __('Too many god submissions. Please wait before sending another.'),
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

        Health::checks([
            CpuLoadCheck::new()
                ->label('CPU Load')
                ->warnWhenLoadPerCoreIsAbove(0.85)
                ->failWhenLoadPerCoreIsAbove(1.25),
            UsedDiskSpaceCheck::new()
                ->label('Disk Space')
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            DatabaseCheck::new()->label('Database'),
            CacheCheck::new()->label('Cache'),
            DebugModeCheck::new()
                ->label('Debug Mode (APP_DEBUG)')
                ->expectedToBe(false),
            EnvironmentCheck::new()
                ->label('Production Environment (APP_ENV)')
                ->expectEnvironment('production'),
            OptimizedAppCheck::new()->label('Optimized Application'),
        ]);
    }

    protected function configureProductionHttps(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (! app()->environment('production')) {
            return;
        }

        URL::forceScheme('https');
    }

    /**
     * Alt alanlarda asset/vite aynı hosttan yüklensin (mixed content / çapraz köken sorunları).
     * Ana site linkleri PublicSiteUrls::main() ile kalır.
     */
    protected function configurePublicPortalUrlRoot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (! PublicSiteUrls::isPublicSubdomainRequest()) {
            return;
        }

        $request = request();
        $scheme = app()->environment('production') || $request->isSecure()
            ? 'https'
            : $request->getScheme();

        URL::forceRootUrl("{$scheme}://".$request->getHost());
        URL::forceScheme($scheme);
    }

    protected function configureLocalSessionForGazetteSubdomain(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (env('SESSION_DOMAIN') !== null && env('SESSION_DOMAIN') !== '') {
            return;
        }

        $host = request()->getHost();
        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            config(['session.domain' => '.localhost']);
        }
    }
}
