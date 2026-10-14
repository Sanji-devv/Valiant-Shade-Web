<?php

namespace App\ValiantShade\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogValiantShadeActivity
{
    /** @var array<int, string> */
    protected array $skipPatterns = [
        '*/static/*',
        '*/build/*',
        '*/lang/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldLog($request, $response)) {
            return $response;
        }

        $user = $request->user();
        $routeName = $request->route()?->getName();

        activity('http')
            ->causedBy($user)
            ->event($user ? 'http.authenticated' : 'http.guest')
            ->withProperties([
                'method' => $request->method(),
                'route' => $routeName,
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'ip' => $request->ip(),
                'user_agent' => substr((string) ($request->userAgent() ?? ''), 0, 255),
            ])
            ->log($user
                ? __('HTTP :method :route', ['method' => $request->method(), 'route' => $routeName ?? $request->path()])
                : __('Guest HTTP :method :route', ['method' => $request->method(), 'route' => $routeName ?? $request->path()]));

        return $response;
    }

    protected function shouldLog(Request $request, Response $response): bool
    {
        if (! config('activitylog.enabled')) {
            return false;
        }

        foreach ($this->skipPatterns as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return false;
        }

        if ($response->getStatusCode() >= 500) {
            return false;
        }

        return true;
    }
}
