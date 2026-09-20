<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('admin.*', function ($view): void {
            \Illuminate\Support\Facades\Vite::useHotFile(public_path('static/admin.hot'));
            \Illuminate\Support\Facades\Vite::useBuildDirectory('static/admin/build');

            $verticalMenuJson = file_get_contents(base_path('resources/menu/admin/verticalMenu.json'));
            $verticalMenuData = json_decode($verticalMenuJson);
            $horizontalMenuJson = file_get_contents(base_path('resources/menu/admin/horizontalMenu.json'));
            $horizontalMenuData = json_decode($horizontalMenuJson);

            $view->with('menuData', [$verticalMenuData, $horizontalMenuData]);
        });

        Route::middleware(['web', \App\Http\Middleware\Admin\LocaleMiddleware::class])
            ->prefix('admin')
            ->group(base_path('routes/admin.php'));
    }
}
