<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class NewspaperController extends Controller
{
    public function show(): View
    {
        return view('web.newspaper', [
            'newsTypes' => config('news.types', []),
        ]);
    }
}
