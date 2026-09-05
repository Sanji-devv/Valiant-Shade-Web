<?php

namespace DemoVuexy\Http\Controllers\apps;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvoiceAdd extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.apps.app-invoice-add');
  }
}
