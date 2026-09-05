<?php

namespace DemoVuexy\Http\Controllers\layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContentNavSidebar extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.layouts-example.layouts-content-navbar-with-sidebar');
  }
}
