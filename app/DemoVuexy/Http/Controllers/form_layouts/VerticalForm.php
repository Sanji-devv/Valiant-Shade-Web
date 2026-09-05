<?php

namespace DemoVuexy\Http\Controllers\form_layouts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VerticalForm extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.form-layout.form-layouts-vertical');
  }
}
