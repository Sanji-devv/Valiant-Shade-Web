<?php

use App\Http\Controllers\Api\PublicGodController;
use App\Http\Controllers\Web\GodsPantheonController;
use Illuminate\Support\Facades\Route;

return function (bool $registerNames = true): void {
    $pantheon = Route::get('/', [GodsPantheonController::class, 'index']);
    if ($registerNames) {
        $pantheon->name('web.gods-pantheon.subdomain');
    }

    Route::redirect('/gods-pantheon', '/', 301);

    Route::prefix('api')->group(function () use ($registerNames): void {
        Route::prefix('public/gods')->group(function () use ($registerNames): void {
            $bootstrap = Route::get('/bootstrap', [PublicGodController::class, 'bootstrap']);
            $show = Route::get('/{slug}', [PublicGodController::class, 'show'])->where('slug', '[a-z0-9-]+');
            $submit = Route::post('/submissions', [PublicGodController::class, 'submit'])
                ->middleware('throttle:public.god.submissions');
            $correction = Route::post('/{slug}/corrections', [PublicGodController::class, 'submitCorrection'])
                ->where('slug', '[a-z0-9-]+')
                ->middleware('throttle:public.god.submissions');

            if ($registerNames) {
                $bootstrap->name('api.public.gods.bootstrap.subdomain');
                $show->name('api.public.gods.show.subdomain');
                $submit->name('api.public.gods.submit.subdomain');
                $correction->name('api.public.gods.corrections.subdomain');
            }
        });
    });
};