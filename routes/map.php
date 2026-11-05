<?php

use App\Http\Controllers\Api\TerritoryMapController;
use App\Http\Controllers\Web\ImageMapController;
use App\Http\Controllers\Web\IsonburgCityController;
use App\Http\Controllers\Web\MapPageController;
use App\Http\Controllers\Web\ValiantShadeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dünya haritası alt alan adı (map.{domain})
|--------------------------------------------------------------------------
*/

Route::get('/feldan', [ImageMapController::class, 'feldan'])->name('web.feldan');

Route::get('/rovesta', [ImageMapController::class, 'rovestaKingdom'])->name('web.rovesta');
Route::get('/isonburg', [ImageMapController::class, 'isonburgKingdom'])->name('web.isonburg');
Route::get('/isonburg/sehir', [IsonburgCityController::class, 'show'])->name('web.isonburg.city');
Route::get('/isonburg/arazi', fn () => app(MapPageController::class)->show('isonburg'))->name('web.isonburg.terrain');

Route::prefix('world')->group(function () {
    Route::get('/valiant-shade', [ValiantShadeController::class, 'show'])->name('web.valiant-shade');
});

Route::prefix('api')->group(function () {
    Route::get('/maps/{slug}', [TerritoryMapController::class, 'show']);
});
