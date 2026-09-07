<?php

use App\Http\Controllers\Api\BuildingTypeController;
use App\Http\Controllers\Api\MapBuildingController;
use App\Http\Controllers\Api\MapEditorController;
use App\Http\Controllers\Api\MapTerrainController;
use App\Http\Controllers\Api\TerritoryMapController;
use App\Http\Controllers\Web\ImageMapController;
use App\Http\Controllers\Web\IsonburgCityController;
use App\Http\Controllers\Web\MapPageController;
use App\Http\Controllers\Web\ValiantShadeController;
use App\Http\Controllers\Web\MapAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ImageMapController::class, 'feldan'])->name('web.feldan');
Route::get('/feldan', [ImageMapController::class, 'feldan']);

Route::get('/isonburg', [ImageMapController::class, 'isonburgKingdom'])->name('web.isonburg');
Route::get('/isonburg/sehir', [IsonburgCityController::class, 'show'])->name('web.isonburg.city');
Route::get('/isonburg/sehir/edit', [IsonburgCityController::class, 'edit'])
    ->middleware(['auth', 'map.editor'])
    ->name('web.isonburg.city.edit');

Route::get('/isonburg/arazi', fn () => app(MapPageController::class)->show('isonburg'))->name('web.isonburg.terrain');
Route::get('/isonburg/arazi/edit', fn () => app(MapPageController::class)->edit('isonburg'))
    ->middleware(['auth', 'map.editor'])
    ->name('web.isonburg.terrain.edit');

Route::get('/home', function () {
    return view('web.home');
})->name('web.home');

Route::get('/valiant-shade', [ValiantShadeController::class, 'show'])->name('web.valiant-shade');
Route::get('/valiant-shade/edit', fn () => app(MapPageController::class)->edit('valiant-shade'))
    ->middleware(['auth', 'map.editor'])
    ->name('web.valiant-shade.edit');

Route::get('/login', [MapAuthController::class, 'showLogin'])->name('web.login');
Route::post('/login', [MapAuthController::class, 'login'])->name('web.login.submit');
Route::post('/logout', [MapAuthController::class, 'logout'])->name('web.logout');

Route::prefix('api')->group(function () {
    Route::get('/maps/{slug}', [TerritoryMapController::class, 'show'])->name('api.maps.show');

    Route::middleware(['auth', 'map.editor'])->group(function () {
        Route::post('/maps/{slug}/save', [MapEditorController::class, 'save'])->name('api.maps.save');
        Route::post('/maps/{slug}/buildings', [MapBuildingController::class, 'store'])->name('api.map-buildings.store');
        Route::patch('/map-buildings/{mapBuilding}', [MapBuildingController::class, 'update'])->name('api.map-buildings.update');
        Route::delete('/map-buildings/{mapBuilding}', [MapBuildingController::class, 'destroy'])->name('api.map-buildings.destroy');

        Route::patch('/maps/{slug}/terrain', [MapTerrainController::class, 'paint'])->name('api.maps.terrain.paint');
        Route::patch('/maps/{slug}/dimensions', [MapTerrainController::class, 'resize'])->name('api.maps.dimensions.resize');
        Route::post('/maps/{slug}/tile-types', [MapTerrainController::class, 'storeTileType'])->name('api.maps.tile-types.store');
        Route::patch('/map-tile-types/{mapTileType}', [MapTerrainController::class, 'updateTileType'])->name('api.map-tile-types.update');

        Route::post('/building-types', [BuildingTypeController::class, 'store'])->name('api.building-types.store');
        Route::post('/building-types/{buildingType}/sprite', [BuildingTypeController::class, 'uploadSprite'])->name('api.building-types.sprite');
        Route::delete('/building-types/{buildingType}/sprite', [BuildingTypeController::class, 'clearSprite'])->name('api.building-types.sprite.clear');
    });
});
