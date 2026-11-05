<?php

namespace App\ValiantShade\Http\Middleware;

use App\ValiantShade\Services\ValiantShadeRouteAuthorization;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureValiantShadePanelAccess
{
    public function __construct(
        protected ValiantShadeRouteAuthorization $authorization,
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if ($this->authorization->isPublic($request, $routeName)) {
            return $next($request);
        }

        if (! Auth::check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => __('Authentication required.'),
                    'redirect' => route('valiant-shade.auth-login-basic'),
                ], 401);
            }

            return redirect()->guest(route('valiant-shade.auth-login-basic'));
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ((int) ($user->status ?? 1) !== 1) {
            abort(403, __('Your account is not active.'));
        }

        $rule = $this->authorization->accessRule($routeName);

        if ($this->authorization->userMayAccess($user, $rule)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => __('Access denied. You do not have permission for this section.'),
            ], 403);
        }

        if ($rule === '__panel__' && $this->authorization->userMayAccess($user, null)) {
            return redirect()
                ->route('valiant-shade.pages-profile-user')
                ->with('error', __('Access denied. You do not have permission for this section.'));
        }

        abort(403, __('Access denied. You do not have permission for this section.'));
    }
}
