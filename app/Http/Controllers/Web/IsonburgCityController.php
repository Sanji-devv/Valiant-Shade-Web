<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class IsonburgCityController extends Controller
{
    public function show(): View
    {
        return view('web.world.isonburg-sehir', [
            'mapSlug' => 'isonburg-sehir',
        ]);
    }

    public function edit(): View
    {
        return view('web.valiant-shade.map', [
            'canEdit' => true,
            'mapSlug' => 'isonburg-sehir',
            'mapName' => 'Isonburg Şehri',
        ]);
    }
}
