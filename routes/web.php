<?php

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

Route::get('/login', function () {
    return redirect()->route('valiant-shade.auth-login-basic');
})->name('login');

Route::post('/logout', [\App\ValiantShade\Http\Controllers\authentications\LogoutController::class, 'logout'])->name('logout.root');