<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PublicSiteUrls;
use App\ValiantShade\Support\NpcRaceCatalog;
use Illuminate\View\View;

class NpcRosterController extends Controller
{
    public function index(): View
    {
        $brand = config('npc', []);
        $canonical = PublicSiteUrls::isNpcSubdomainRequest()
            ? PublicSiteUrls::npcRoster('/')
            : PublicSiteUrls::main('/npc-roster');

        return view('web.npc-roster', [
            'npcBrand' => $brand,
            'mainSiteUrl' => PublicSiteUrls::main(),
            'gazetteUrl' => PublicSiteUrls::gazette('/'),
            'mapUrl' => PublicSiteUrls::map('/feldan'),
            'npcUrl' => PublicSiteUrls::npcRoster('/'),
            'canonicalUrl' => $canonical,
            'pfsrdRaceNames' => NpcRaceCatalog::names(),
        ]);
    }
}