<?php

namespace DemoVuexy\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class DemoVuexyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('demo-vuexy.*', function ($view): void {
            $verticalMenuJson = file_get_contents(base_path('resources/menu/demo-vuexy/verticalMenu.json'));
            $verticalMenuData = json_decode($verticalMenuJson);
            $horizontalMenuJson = file_get_contents(base_path('resources/menu/demo-vuexy/horizontalMenu.json'));
            $horizontalMenuData = json_decode($horizontalMenuJson);

            $view->with('menuData', [$verticalMenuData, $horizontalMenuData]);
        });

        Route::middleware(['web', \DemoVuexy\Http\Middleware\LocaleMiddleware::class])
            ->prefix('demo-vuexy')
            ->group(base_path('routes/demo-vuexy.php'));
    }
}
