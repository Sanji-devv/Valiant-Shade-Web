<?php

namespace DemoVuexy\Http\Controllers\authentications;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegisterMultiSteps extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('demo-vuexy.content.authentications.auth-register-multisteps', ['pageConfigs' => $pageConfigs]);
  }
}
