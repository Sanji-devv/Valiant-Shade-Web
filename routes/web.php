<?php

use App\Http\Controllers\Web\GodsPantheonController;
use App\Http\Controllers\Web\NpcRosterController;
use App\Http\Controllers\Web\PublicSeoController;
use App\Support\PublicSiteUrls;
use App\ValiantShade\Http\Controllers\authentications\LogoutController;
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

Route::get('/npcs', fn () => redirect(PublicSiteUrls::npcRoster(), 301))->name('web.npcs.redirect');
Route::get('/npc-roster', [NpcRosterController::class, 'index'])->name('web.npc-roster');

Route::get('/login', function () {
    return redirect()->route('valiant-shade.auth-login-basic');
})->name('login');

Route::post('/logout', [LogoutController::class, 'logout'])->name('logout.root');

Route::get('/gods-pantheon', [GodsPantheonController::class, 'index'])->name('web.gods-pantheon');
Route::get('/gods', fn () => redirect(PublicSiteUrls::godsPantheon('/'), 301))->name('web.gods.index');
Route::get('/gods/{slug}', fn (string $slug) => redirect(PublicSiteUrls::godsPantheon('/?q='.$slug), 301))
    ->where('slug', '[a-z0-9-]+')
    ->name('web.gods.show');

require __DIR__.'/world-maps.php';
