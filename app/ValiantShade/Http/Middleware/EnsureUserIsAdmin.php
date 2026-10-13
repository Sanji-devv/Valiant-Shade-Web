<?php

namespace App\ValiantShade\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     * Enforce strict, bulletproof access control: ONLY Administrators may proceed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Authentication check
        if (!Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Authentication required. Only administrators can access this section.'),
                ], 401);
            }

            return redirect()
                ->route('valiant-shade.auth-login-basic')
                ->with('error', __('Authentication required. Only administrators can access this section.'));
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // 2. Strict Administrator Role Authorization
        if (!$user->isAdmin()) {
            Log::warning(sprintf(
                'Security Alert: Unauthorized admin access attempt by user ID %d (%s) with role "%s" to [%s]. IP: %s',
                $user->id ?? 0,
                $user->email ?? 'no-email',
                $user->role ?? 'none',
                $request->fullUrl(),
                $request->ip()
            ));

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Access denied. Strictly restricted to system Administrators.'),
                ], 403);
            }

            abort(403, __('Access denied. Strictly restricted to system Administrators.'));
        }

        return $next($request);
    }
}
