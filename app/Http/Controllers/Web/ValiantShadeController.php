<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ValiantShadeController extends Controller
{
    public function show(): View
    {
        return view('web.world.valiant-shade-guild', [
            'mapSlug' => 'valiant-shade',
            'artConfig' => config('world-maps.valiant-shade-guild'),
        ]);
    }
}
