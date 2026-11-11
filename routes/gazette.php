<?php

use App\Http\Controllers\Api\PublicNewsController;
use App\Http\Controllers\Web\NewspaperController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gazete alt alan adı (gazette.{domain})
|--------------------------------------------------------------------------
|
| Halka açık gazete arayüzü; admin haber yönetimi ana domainde kalır.
|
*/

Route::get('/', [NewspaperController::class, 'show'])->name('web.newspaper');

Route::redirect('/newspaper', '/', 301);

Route::prefix('api')->group(function () {
    Route::prefix('public/news')->group(function () {
        Route::get('/bootstrap', [PublicNewsController::class, 'bootstrap']);
        Route::get('/gazette-issues', [PublicNewsController::class, 'gazetteIssues']);
        Route::get('/featured', [PublicNewsController::class, 'featured']);
        Route::get('/', [PublicNewsController::class, 'index']);
        Route::get('/{id}', [PublicNewsController::class, 'show'])->whereNumber('id');
        Route::post('/submissions', [PublicNewsController::class, 'submit'])
            ->middleware('throttle:public.news.submissions');
        Route::post('/flash-submissions', [PublicNewsController::class, 'submitFlash'])
            ->middleware('throttle:public.news.submissions');
        Route::post('/extra-issues', [PublicNewsController::class, 'publishExtra'])
            ->middleware(['auth', 'throttle:public.news.submissions']);
    });
});
