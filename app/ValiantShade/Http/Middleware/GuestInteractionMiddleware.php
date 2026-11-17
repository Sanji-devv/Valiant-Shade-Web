<?php

namespace App\ValiantShade\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class GuestInteractionMiddleware
{
    /**
     * Route names allowed for guest mutating requests (auth actions).
     *
     * @var array<string>
     */
    protected array $allowedRouteNames = [
        'valiant-shade.auth-login-basic',
        'valiant-shade.auth-login-basic.post',
        'valiant-shade.auth-register-basic',
        'valiant-shade.auth-register-basic.post',
        'valiant-shade.auth-reset-password-basic',
        'valiant-shade.auth-forgot-password-basic',
        'valiant-shade.pages-account-settings-account.update',
        'valiant-shade.pages-account-settings-account.deactivate',
        'valiant-shade.gods.index',
        'valiant-shade.gods.show',
        'valiant-shade.gods.update',
        'valiant-shade.gods.profile.update',
        'login',
    ];

    /**
     * URL patterns allowed for guest mutating requests.
     *
     * @var array<string>
     */
    protected array $allowedUrlPatterns = [
        '*/auth/login*',
        '*/auth/register*',
        '*/auth/reset-password*',
        '*/auth/forgot-password*',
        '*/pages/account-settings-account*',
        'login',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Authenticated users can perform any action
        if (Auth::check()) {
            return $next($request);
        }

        // Allow guest authentication actions (login, register, reset password)
        $routeName = $request->route() ? $request->route()->getName() : null;
        if ($routeName && in_array($routeName, $this->allowedRouteNames, true)) {
            return $next($request);
        }

        foreach ($this->allowedUrlPatterns as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        // Any other mutating interaction by a guest is blocked and redirected to login
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Please sign in to perform this action.',
                'redirect' => route('valiant-shade.auth-login-basic'),
            ], 401);
        }

        return redirect()->guest(route('valiant-shade.auth-login-basic'))
            ->with('warning', 'Please sign in to perform this action.');
    }
}
