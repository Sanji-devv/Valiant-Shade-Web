<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WithoutMenu extends Controller
{
  public function index()
  {

    return view('demo-vuexy.content.layouts-example.layouts-without-menu');
  }
}
