<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMapEditor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                abort(401, 'Giriş gerekli.');
            }

            return redirect()->guest(route('web.login', [
                'redirect' => $request->fullUrl(),
            ]));
        }

        if (! $user->is_admin) {
            if ($request->expectsJson()) {
                abort(403, 'Harita düzenleme yetkisi gerekli.');
            }

            return redirect()->route('web.valiant-shade');
        }

        return $next($request);
    }
}
