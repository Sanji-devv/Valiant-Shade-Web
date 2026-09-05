<?php

namespace DemoVuexy\Http\Controllers\form_validation;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Validation extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.form-validation.form-validation');
  }
}
