<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\language\LanguageController;
use App\Http\Controllers\Admin\pages\HomePage;
use App\Http\Controllers\Admin\pages\Page2;
use App\Http\Controllers\Admin\pages\MiscError;
use App\Http\Controllers\Admin\authentications\LoginBasic;
use App\Http\Controllers\Admin\authentications\RegisterBasic;

// Main Page Route
Route::get('/', [HomePage::class, 'index'])->name('admin.pages-home');
Route::get('/page-2', [Page2::class, 'index'])->name('admin.pages-page-2');

// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap']);
Route::get('/pages/misc-error', [MiscError::class, 'index'])->name('admin.pages-misc-error');

// authentication
Route::get('/auth/login-basic', [LoginBasic::class, 'index'])->name('admin.auth-login-basic');
Route::get('/auth/register-basic', [RegisterBasic::class, 'index'])->name('admin.auth-register-basic');
