<?php

namespace DemoVuexy\Http\Controllers\icons;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FontAwesome extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.icons.icons-font-awesome');
  }
}
