<?php

namespace DemoVuexy\Http\Controllers\user_interface;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Typography extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.user-interface.ui-typography');
  }
}
