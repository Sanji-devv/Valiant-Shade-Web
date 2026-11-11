<?php

use App\Http\Controllers\Web\NewspaperController;
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

Route::get('/newspaper', function () {
    if (app()->environment('local')) {
        return redirect('/gazette', 302);
    }

    return redirect()->route('web.newspaper', status: 301);
});

/*
| Yerel geliştirme: gazette.localhost oturumu ana domainle paylaşmaz.
| Panelde giriş yaptıktan sonra aynı hostta gazete + sıcak haber butonu için:
| http://localhost:PORT/gazette
*/
if (app()->environment('local')) {
    Route::get('/gazette', [NewspaperController::class, 'show'])->name('web.newspaper.local');
}

Route::get('/login', function () {
    return redirect()->route('valiant-shade.auth-login-basic');
})->name('login');

Route::post('/logout', [\App\ValiantShade\Http\Controllers\authentications\LogoutController::class, 'logout'])->name('logout.root');

require __DIR__.'/world-maps.php';