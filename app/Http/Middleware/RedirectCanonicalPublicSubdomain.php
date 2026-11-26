<?php

namespace App\Http\Middleware;

use App\Support\PublicSiteUrls;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * İngilizce alt alan adlarını (map / gazette) Türkçe kanonik hosta (harita / gazete) yönlendirir.
 */
class RedirectCanonicalPublicSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        $aliases = [
            ...(array) config('map.alias_domains', []),
            ...(array) config('gazette.alias_domains', []),
            ...(array) config('npc.alias_domains', []),
        ];

        if (! in_array($host, $aliases, true)) {
            return $next($request);
        }

        $canonical = null;
        if (in_array($host, (array) config('map.alias_domains', []), true)) {
            $canonical = (string) config('map.domain');
        } elseif (in_array($host, (array) config('gazette.alias_domains', []), true)) {
            $canonical = (string) config('gazette.domain');
        } elseif (in_array($host, (array) config('npc.alias_domains', []), true)) {
            $canonical = (string) config('npc.domain');
        }

        if ($canonical === '' || $canonical === $host) {
            return $next($request);
        }

        $target = PublicSiteUrls::hostUrl($canonical, '/');
        $path = $request->getPathInfo();
        $query = $request->getQueryString();

        $target = rtrim($target, '/').($path !== '' ? $path : '');
        if ($query) {
            $target .= '?'.$query;
        }

        return redirect()->away($target, 301);
    }
}