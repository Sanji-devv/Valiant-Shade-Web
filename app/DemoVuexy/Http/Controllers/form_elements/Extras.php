<?php

namespace DemoVuexy\Http\Controllers\form_elements;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Extras extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.form-elements.forms-extras');
  }
}
