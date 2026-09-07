<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TerritoryMap;

class MapPageController extends Controller
{
    public function show(string $slug)
    {
        $map = TerritoryMap::query()->where('slug', $slug)->firstOrFail();

        return view('web.valiant-shade.map', [
            'canEdit' => false,
            'mapSlug' => $map->slug,
            'mapName' => $map->name,
        ]);
    }

    public function edit(string $slug)
    {
        $map = TerritoryMap::query()->where('slug', $slug)->firstOrFail();

        return view('web.valiant-shade.map', [
            'canEdit' => true,
            'mapSlug' => $map->slug,
            'mapName' => $map->name,
        ]);
    }
}
