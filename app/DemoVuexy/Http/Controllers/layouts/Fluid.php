<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Fluid extends Controller
{
  public function index()
  {
    $pageConfigs = ['contentLayout' => 'wide'];
    return view('demo-vuexy.content.layouts-example.layouts-fluid',['pageConfigs'=> $pageConfigs]);
  }
}
