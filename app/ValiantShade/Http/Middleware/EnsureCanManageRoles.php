<?php

namespace App\ValiantShade\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageRoles
{
    public function handle(Request $request, Closure $next, string $ability = 'read'): Response
    {
        if (! Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Authentication required.'),
                ], 401);
            }

            return redirect()
                ->route('valiant-shade.auth-login-basic')
                ->with('error', __('Authentication required.'));
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $permission = match ($ability) {
            'write', 'update', 'mutate' => 'roles.update',
            default => 'roles.read',
        };

        $allowed = $user->isAdmin()
            || $user->hasPermission('roles.all')
            || $user->hasPermission($permission);

        if (! $allowed) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Access denied. You do not have permission to manage roles.'),
                ], 403);
            }

            abort(403, __('Access denied. You do not have permission to manage roles.'));
        }

        return $next($request);
    }
}
