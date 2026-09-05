<?php

namespace DemoVuexy\Http\Controllers\front_pages;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Checkout extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'front'];
    return view('demo-vuexy.content.front-pages.checkout-page', ['pageConfigs' => $pageConfigs]);
  }
}
