<?php

namespace DemoVuexy\Http\Controllers\wizard_example;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Checkout extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.wizard-example.wizard-ex-checkout');
  }
}
