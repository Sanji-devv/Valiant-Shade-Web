<?php

use App\Http\Controllers\Web\PublicSeoController;
use App\Support\PublicSiteUrls;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend (Public User) Routes
|--------------------------------------------------------------------------
|
| Üye / ziyaretçi tarafı rotaları burada tanımlanır.
| Blade şablonları: resources/views/web/
|
*/

Route::get('/', function () {
    return view('web.home');
})->name('web.home');

Route::get('/sitemap.xml', [PublicSeoController::class, 'sitemap'])->name('web.sitemap');
Route::get('/robots.txt', [PublicSeoController::class, 'robots'])->name('web.robots');
Route::get('/llms.txt', [PublicSeoController::class, 'llms'])->name('web.llms');
Route::get('/llms-full.txt', [PublicSeoController::class, 'llmsFull'])->name('web.llms-full');

Route::get('/newspaper', fn () => redirect(PublicSiteUrls::gazette(), 301));

Route::get('/gazette', fn () => redirect(PublicSiteUrls::gazette(), 301));

Route::get('/login', function () {
    return redirect()->route('valiant-shade.auth-login-basic');
})->name('login');

Route::post('/logout', [\App\ValiantShade\Http\Controllers\authentications\LogoutController::class, 'logout'])->name('logout.root');

require __DIR__.'/world-maps.php';