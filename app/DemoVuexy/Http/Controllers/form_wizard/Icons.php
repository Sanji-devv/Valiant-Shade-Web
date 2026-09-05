<?php

namespace DemoVuexy\Http\Controllers\form_wizard;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Icons extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.form-wizard.form-wizard-icons');
  }
}
