<?php

namespace DemoVuexy\Http\Controllers\tables;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Basic extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.tables.tables-basic');
  }
}
