<?php

namespace DemoVuexy\Http\Controllers\icons;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Tabler extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.icons.icons-tabler');
  }
}
