<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ImageMapController extends Controller
{
    public function feldan(): View
    {
        return $this->render('feldan');
    }

    public function isonburgKingdom(): View
    {
        return $this->render('isonburg-kingdom');
    }

    public function isonburgCityArt(): View
    {
        return $this->render('isonburg-city-art');
    }

    private function render(string $key): View
    {
        $config = config("world-maps.{$key}");

        abort_if(! $config, 404);

        return view('web.world.image-map', [
            'mapKey' => $key,
            'config' => $config,
        ]);
    }
}
