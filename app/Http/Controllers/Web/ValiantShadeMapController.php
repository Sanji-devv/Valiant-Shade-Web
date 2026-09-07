<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ValiantShadeMapController extends Controller
{
    public function show()
    {
        return view('web.valiant-shade.map', [
            'canEdit' => false,
            'mapSlug' => 'valiant-shade',
        ]);
    }

    public function edit()
    {
        return view('web.valiant-shade.map', [
            'canEdit' => true,
            'mapSlug' => 'valiant-shade',
        ]);
    }
}
