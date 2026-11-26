<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PublicSiteUrls;
use Illuminate\View\View;

class GodsPantheonController extends Controller
{
    public function index(): View
    {
        $brand = config('gods_public', []);
        $canonical = PublicSiteUrls::isGodsSubdomainRequest()
            ? PublicSiteUrls::godsPantheon('/')
            : PublicSiteUrls::main('/gods-pantheon');

        return view('web.gods-pantheon', [
            'godsBrand' => $brand,
            'mainSiteUrl' => PublicSiteUrls::main(),
            'gazetteUrl' => PublicSiteUrls::gazette('/'),
            'mapUrl' => PublicSiteUrls::map('/feldan'),
            'npcUrl' => PublicSiteUrls::npcRoster('/'),
            'godsUrl' => PublicSiteUrls::godsPantheon('/'),
            'canonicalUrl' => $canonical,
        ]);
    }
}