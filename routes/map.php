<?php

use App\Http\Controllers\Api\TerritoryMapController;
use App\Http\Controllers\Web\ImageMapController;
use App\Http\Controllers\Web\IsonburgCityController;
use App\Http\Controllers\Web\MapPageController;
use App\Http\Controllers\Web\ValiantShadeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dünya haritası alt alan adları (map / harita.{domain})
|--------------------------------------------------------------------------
|
| bootstrap/app.php — $registerNames yalnızca birincil host için true.
|
*/

return function (bool $registerNames = true): void {
    $maybeName = static function ($route, string $routeName) use ($registerNames) {
        if ($registerNames) {
            $route->name($routeName);
        }

        return $route;
    };

    Route::get('/', function () use ($registerNames) {
        if ($registerNames) {
            return redirect()->route('web.feldan');
        }

        return redirect('/feldan');
    });

    $maybeName(
        Route::get('/feldan', [ImageMapController::class, 'feldan']),
        'web.feldan',
    );

    $maybeName(Route::get('/rovesta', [ImageMapController::class, 'rovestaKingdom']), 'web.rovesta');
    $maybeName(Route::get('/isonburg', [ImageMapController::class, 'isonburgKingdom']), 'web.isonburg');
    $maybeName(Route::get('/isonburg/sehir', [IsonburgCityController::class, 'show']), 'web.isonburg.city');
    $maybeName(
        Route::get('/isonburg/arazi', fn () => app(MapPageController::class)->show('isonburg')),
        'web.isonburg.terrain',
    );

    Route::prefix('world')->group(function () use ($maybeName) {
        $maybeName(Route::get('/valiant-shade', [ValiantShadeController::class, 'show']), 'web.valiant-shade');
    });

    Route::prefix('api')->group(function () {
        Route::get('/maps/{slug}', [TerritoryMapController::class, 'show']);
    });
};