<?php

namespace DemoVuexy\Http\Controllers\form_elements;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BasicInput extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.form-elements.forms-basic-inputs');
  }
}
