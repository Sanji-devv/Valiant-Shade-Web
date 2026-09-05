<?php

namespace DemoVuexy\Http\Controllers\dashboard;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Analytics extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.dashboard.dashboards-analytics');
  }
}
