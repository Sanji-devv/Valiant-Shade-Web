<?php

namespace App\ValiantShade\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ValiantShadeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\ValiantShade\Contracts\InventoryRepositoryInterface::class,
            \App\ValiantShade\Repositories\InventoryRepository::class
        );

        $this->app->bind(
            \App\ValiantShade\Contracts\InventoryCalculationServiceInterface::class,
            \App\ValiantShade\Services\InventoryCalculationService::class
        );

        $this->app->bind(
            \App\ValiantShade\Contracts\GuildVaultRepositoryInterface::class,
            \App\ValiantShade\Repositories\GuildVaultRepository::class
        );

        $this->app->bind(
            \App\ValiantShade\Contracts\PlayerXpRepositoryInterface::class,
            \App\ValiantShade\Repositories\PlayerXpRepository::class
        );
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Vite::useHotFile(public_path('static/valiant-shade.hot'));
        \Illuminate\Support\Facades\Vite::useBuildDirectory('static/valiant-shade/build');

        View::composer('valiant-shade.*', function ($view): void {
            \Illuminate\Support\Facades\Vite::useHotFile(public_path('static/valiant-shade.hot'));
            \Illuminate\Support\Facades\Vite::useBuildDirectory('static/valiant-shade/build');

            $verticalMenuJson = file_get_contents(base_path('resources/menu/valiant-shade/verticalMenu.json'));
            $verticalMenuData = json_decode($verticalMenuJson);
            $horizontalMenuJson = file_get_contents(base_path('resources/menu/valiant-shade/horizontalMenu.json'));
            $horizontalMenuData = json_decode($horizontalMenuJson);

            $view->with('menuData', [$verticalMenuData, $horizontalMenuData]);
        });

        Route::middleware([
            'web',
            \App\ValiantShade\Http\Middleware\LocaleMiddleware::class,
            \App\ValiantShade\Http\Middleware\EnsureValiantShadePanelAccess::class,
            \App\ValiantShade\Http\Middleware\GuestInteractionMiddleware::class,
            \App\ValiantShade\Http\Middleware\LogValiantShadeActivity::class,
        ])
            ->prefix('valiant-shade')
            ->group(base_path('routes/valiant-shade.php'));
    }
}
