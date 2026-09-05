<?php

namespace DemoVuexy\Http\Controllers\pages;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountSettingsSecurity extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.pages.pages-account-settings-security');
  }
}
