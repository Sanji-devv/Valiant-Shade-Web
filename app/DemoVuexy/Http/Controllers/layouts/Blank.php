<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Blank extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'blank'];
    return view('demo-vuexy.content.layouts-example.layouts-blank', ['pageConfigs' => $pageConfigs]);
  }
}
