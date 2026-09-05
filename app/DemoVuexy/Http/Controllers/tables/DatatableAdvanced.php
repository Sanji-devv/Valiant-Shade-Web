<?php

namespace DemoVuexy\Http\Controllers\tables;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DatatableAdvanced extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.tables.tables-datatables-advanced');
  }
}
