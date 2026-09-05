<?php

namespace DemoVuexy\Http\Controllers\front_pages;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Payment extends Controller
{
  public function index()
  {
    $pageConfigs = ['myLayout' => 'front'];
    return view('demo-vuexy.content.front-pages.payment-page', ['pageConfigs' => $pageConfigs]);
  }
}
