<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'map.editor' => \App\Http\Middleware\EnsureMapEditor::class,
        ]);
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
