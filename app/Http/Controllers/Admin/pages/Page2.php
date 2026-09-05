<?php

namespace App\Http\Controllers\Admin\pages;

use App\Http\Controllers\Admin\Controller;
use Illuminate\Http\Request;

class Page2 extends Controller
{
  public function index()
  {
    return view('admin.content.pages.pages-page2');
  }
}
