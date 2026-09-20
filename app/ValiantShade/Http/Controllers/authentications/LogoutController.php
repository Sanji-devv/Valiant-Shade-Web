<?php

namespace App\ValiantShade\Http\Controllers\authentications;

use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(Request $request): RedirectResponse
    {
        // Restore guest theme preference
        $userThemesRaw = $request->cookie('valiant-shade-user-themes');
        $userThemes = $userThemesRaw ? json_decode($userThemesRaw, true) : [];
        if (is_array($userThemes) && !empty($userThemes['guest'])) {
            cookie()->queue(cookie('valiant-shade-mode', $userThemes['guest'], 60 * 24 * 365, '/', null, false, false));
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('valiant-shade.auth-login-basic');
    }
}