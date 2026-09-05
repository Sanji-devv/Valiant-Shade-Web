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
