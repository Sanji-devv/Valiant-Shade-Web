<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class GodsPageController extends Controller
{
    public function index(): View
    {
        return view('valiant-shade.content.pages.pages-gods', [
            'selectedGod' => null,
            'godGroups' => config('gods.groups', []),
            'sourceUrl' => config('gods.source_url'),
        ]);
    }

    public function show(string $slug): View
    {
        $god = collect(config('gods.groups', []))
            ->flatMap(static fn (array $group): array => $group['gods'] ?? [])
            ->firstWhere('slug', $slug);

        abort_if($god === null, 404);

        return view('valiant-shade.content.pages.pages-gods', [
            'selectedGod' => $god,
            'godGroups' => config('gods.groups', []),
            'sourceUrl' => config('gods.source_url'),
        ]);
    }
}
