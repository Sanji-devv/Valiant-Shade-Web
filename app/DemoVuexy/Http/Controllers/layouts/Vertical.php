<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Vertical extends Controller
{
  public function index()
  {

    $pageConfigs = ['myLayout' => 'vertical'];

    return view('demo-vuexy.content.dashboard.dashboards-analytics', ['pageConfigs' => $pageConfigs]);
  }
}
