<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Helpers\Helpers;

class Horizontal extends Controller
{
  public function index()
  {

    $pageConfigs = ['myLayout' => 'horizontal'];

    return view('demo-vuexy.content.dashboard.dashboards-analytics',['pageConfigs'=> $pageConfigs]);
  }
}
