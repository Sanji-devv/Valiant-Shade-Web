<?php

namespace DemoVuexy\Http\Controllers\front_pages;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Landing extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'front'];
    return view('demo-vuexy.content.front-pages.landing-page', ['pageConfigs' => $pageConfigs]);
  }
}
