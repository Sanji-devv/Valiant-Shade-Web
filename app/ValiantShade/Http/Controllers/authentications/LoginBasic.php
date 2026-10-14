<?php

namespace App\ValiantShade\Http\Controllers\authentications;

use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginBasic extends Controller
{
  public function index(Request $request)
  {
    $redirect = $request->query('redirect');
    if (is_string($redirect) && $redirect !== '' && str_starts_with($redirect, url('/'))) {
      redirect()->setIntendedUrl($redirect);
    }

    $pageConfigs = ['myLayout' => 'blank'];
    return view('valiant-shade.content.authentications.auth-login-basic', ['pageConfigs' => $pageConfigs]);
  }

  public function authenticate(Request $request): RedirectResponse
  {
    $request->validate([
      'email-username' => ['required', 'string'],
      'password' => ['required', 'string'],
    ]);

    $loginInput = $request->input('email-username');
    $password = $request->input('password');
    $remember = $request->boolean('remember');

    $throttleKey = Str::transliterate(Str::lower($loginInput).'|'.$request->ip());

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
      $seconds = RateLimiter::availableIn($throttleKey);
      throw ValidationException::withMessages([
        'email-username' => trans('auth.throttle', [
          'seconds' => $seconds,
          'minutes' => ceil($seconds / 60),
        ]),
      ]);
    }

    $fieldType = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
    $credentials = [
      $fieldType => $loginInput,
      'password' => $password,
    ];

    if (!Auth::attempt($credentials, $remember)) {
      RateLimiter::hit($throttleKey);

      throw ValidationException::withMessages([
        'email-username' => __('These credentials do not match our records.'),
      ]);
    }

    RateLimiter::clear($throttleKey);
    $request->session()->regenerate();

    return redirect()->intended(route('valiant-shade.pages-profile-user'));
  }
}