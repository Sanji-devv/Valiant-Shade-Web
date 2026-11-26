<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $registerGazette = require base_path('routes/gazette.php');
            Route::middleware('web')
                ->domain((string) config('gazette.domain'))
                ->group(static fn () => $registerGazette(true));

            $registerMap = require base_path('routes/map.php');
            Route::middleware('web')
                ->domain((string) config('map.domain'))
                ->group(static fn () => $registerMap(true));

            $registerNpc = require base_path('routes/npc.php');
            Route::middleware('web')
                ->domain((string) config('npc.domain'))
                ->group(static fn () => $registerNpc(true));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'map.editor' => \App\Http\Middleware\EnsureMapEditor::class,
        ]);
        // Alias hostlarda rota yok → web grubu çalışmaz; yönlendirme global olmalı.
        $middleware->prepend(\App\Http\Middleware\RedirectCanonicalPublicSubdomain::class);
        $middleware->web(append: [
            \App\ValiantShade\Http\Middleware\LocaleMiddleware::class,
        ]);
        $middleware->encryptCookies(except: [
            'admin-mode',
            'front-mode',
            'valiant-shade-mode',
            'valiant-shade-user-themes',
            'admin-colorPref',
            'front-colorPref',
            'admin-primaryColor',
            'front-primaryColor',
            'customize_skin',
            'customize_semi_dark',
            'LayoutCollapsed',
            'contentLayout',
            'navbarType',
            'headerType',
            'direction',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
