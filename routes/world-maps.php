<?php

use App\Http\Controllers\Api\BuildingTypeController;
use App\Http\Controllers\Api\PublicNewsController;
use App\Http\Controllers\Api\MapBuildingController;
use App\Http\Controllers\Api\MapEditorController;
use App\Http\Controllers\Api\MapTerrainController;
use App\Http\Controllers\Api\TerritoryMapController;
use App\Http\Controllers\Web\IsonburgCityController;
use App\Http\Controllers\Web\MapPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Ana domain: harita yönlendirmeleri, düzenleme rotaları, API
|--------------------------------------------------------------------------
|
| Halka açık harita sayfaları map.{domain} üzerinde (routes/map.php).
|
*/

Route::get('/feldan', fn () => redirect()->route('web.feldan', status: 301));
Route::get('/rovesta', fn () => redirect()->route('web.rovesta', status: 301));
Route::get('/isonburg', fn () => redirect()->route('web.isonburg', status: 301));
Route::get('/isonburg/sehir', fn () => redirect()->route('web.isonburg.city', status: 301));
Route::get('/isonburg/arazi', fn () => redirect()->route('web.isonburg.terrain', status: 301));

Route::prefix('world')->group(function () {
    Route::get('/valiant-shade', fn () => redirect()->route('web.valiant-shade', status: 301));
});

Route::get('/isonburg/sehir/edit', [IsonburgCityController::class, 'edit'])
    ->middleware(['auth', 'map.editor'])
    ->name('web.isonburg.city.edit');

Route::get('/isonburg/arazi/edit', fn () => app(MapPageController::class)->edit('isonburg'))
    ->middleware(['auth', 'map.editor'])
    ->name('web.isonburg.terrain.edit');

Route::prefix('world')->group(function () {
    Route::get('/valiant-shade/edit', fn () => app(MapPageController::class)->edit('valiant-shade'))
        ->middleware(['auth', 'map.editor'])
        ->name('web.valiant-shade.edit');
});

Route::prefix('api')->group(function () {
    Route::prefix('public/news')->group(function () {
        Route::get('/bootstrap', [PublicNewsController::class, 'bootstrap'])->name('api.public.news.bootstrap');
        Route::get('/featured', [PublicNewsController::class, 'featured'])->name('api.public.news.featured');
        Route::get('/', [PublicNewsController::class, 'index'])->name('api.public.news.index');
        Route::get('/{id}', [PublicNewsController::class, 'show'])->whereNumber('id')->name('api.public.news.show');
        Route::post('/submissions', [PublicNewsController::class, 'submit'])
            ->middleware('throttle:public.news.submissions')
            ->name('api.public.news.submit');
        Route::post('/flash-submissions', [PublicNewsController::class, 'submitFlash'])
            ->middleware('throttle:public.news.submissions')
            ->name('api.public.news.flash-submit');
        Route::post('/extra-issues', [PublicNewsController::class, 'publishExtra'])
            ->middleware(['auth', 'throttle:public.news.submissions'])
            ->name('api.public.news.extra-issues');
    });

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
