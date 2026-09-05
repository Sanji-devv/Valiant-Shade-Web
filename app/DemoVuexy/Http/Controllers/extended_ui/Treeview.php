<?php

namespace DemoVuexy\Http\Controllers\extended_ui;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Treeview extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.extended-ui.extended-ui-treeview');
  }
}
