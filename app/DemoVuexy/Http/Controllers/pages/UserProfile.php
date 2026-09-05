<?php

namespace DemoVuexy\Http\Controllers\pages;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserProfile extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.pages.pages-profile-user');
  }
}
