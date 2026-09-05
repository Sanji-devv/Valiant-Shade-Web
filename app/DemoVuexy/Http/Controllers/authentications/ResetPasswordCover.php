<?php

namespace DemoVuexy\Http\Controllers\authentications;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResetPasswordCover extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('demo-vuexy.content.authentications.auth-reset-password-cover', ['pageConfigs' => $pageConfigs]);
  }
}
