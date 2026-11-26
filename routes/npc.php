<?php

use App\Http\Controllers\Api\PublicNpcController;
use App\Http\Controllers\Web\NpcRosterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| NPC sicili alt alan adı (npc.{domain})
|--------------------------------------------------------------------------
*/

return function (bool $registerNames = true): void {
    $roster = Route::get('/', [NpcRosterController::class, 'index']);
    if ($registerNames) {
        $roster->name('web.npc-roster.subdomain');
    }

    Route::redirect('/npc-roster', '/', 301);

    Route::prefix('api')->group(function () use ($registerNames): void {
        Route::prefix('public/npcs')->group(function () use ($registerNames): void {
            $bootstrap = Route::get('/bootstrap', [PublicNpcController::class, 'bootstrap']);
            $suggest = Route::get('/title-suggestions', [PublicNpcController::class, 'titleSuggestions']);
            $facetSuggest = Route::get('/facet-suggestions', [PublicNpcController::class, 'facetSuggestions']);
            $index = Route::get('/', [PublicNpcController::class, 'index']);
            $show = Route::get('/{id}', [PublicNpcController::class, 'show'])->whereNumber('id');
            $submit = Route::post('/submissions', [PublicNpcController::class, 'submit'])
                ->middleware('throttle:public.npc.submissions');
            $correction = Route::post('/{id}/corrections', [PublicNpcController::class, 'submitCorrection'])
                ->whereNumber('id')
                ->middleware('throttle:public.npc.submissions');

            if ($registerNames) {
                $bootstrap->name('api.public.npcs.bootstrap.subdomain');
                $suggest->name('api.public.npcs.title-suggestions.subdomain');
                $facetSuggest->name('api.public.npcs.facet-suggestions.subdomain');
                $index->name('api.public.npcs.index.subdomain');
                $show->name('api.public.npcs.show.subdomain');
                $submit->name('api.public.npcs.submit.subdomain');
                $correction->name('api.public.npcs.corrections.subdomain');
            }
        });
    });
};