<?php

namespace App\ValiantShade\Http\Controllers\apps;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Services\SystemHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function __construct(
        protected SystemHealthService $systemHealthService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $results = $this->systemHealthService->runAndFormat();
        $summary = $this->systemHealthService->summary($results);

        $runtime = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->toIso8601String(),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'summary' => $summary,
                'checks' => $results->values(),
                'runtime' => $runtime,
            ]);
        }

        return view('valiant-shade.content.apps.app-system-health', compact('results', 'summary', 'runtime'));
    }
}
