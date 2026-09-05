<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Container extends Controller
{
  public function index()
  {
    $pageConfigs = ['contentLayout' => 'compact'];
    return view('demo-vuexy.content.layouts-example.layouts-container',['pageConfigs'=> $pageConfigs]);
  }
}
