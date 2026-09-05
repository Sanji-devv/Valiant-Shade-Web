<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CollapsedMenu extends Controller
{
  public function index()
  {

    $pageConfigs = ['menuCollapsed' => true];

    return view('demo-vuexy.content.layouts-example.layouts-collapsed-menu',['pageConfigs'=> $pageConfigs]);
  }
}
