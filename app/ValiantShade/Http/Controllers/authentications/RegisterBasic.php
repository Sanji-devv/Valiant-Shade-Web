<?php

namespace App\ValiantShade\Http\Controllers\authentications;

use App\Models\User;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterBasic extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('valiant-shade.content.authentications.auth-register-basic', ['pageConfigs' => $pageConfigs]);
  }

  public function register(Request $request): RedirectResponse
  {
    $request->validate([
      'username' => ['required', 'string', 'max:255', 'unique:users,username'],
      'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
      'password' => ['required', 'string', 'min:6'],
      'terms' => ['accepted'],
    ]);

    $user = User::create([
      'username' => $request->input('username'),
      'name' => '',
      'email' => $request->input('email'),
      'password' => $request->input('password'),
      'status' => 1,
    ]);

    Auth::login($user);
    $request->session()->regenerate();

    return redirect()->route('valiant-shade.pages-profile-user');
  }
}