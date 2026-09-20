<?php

namespace App\ValiantShade\Http\Controllers\dashboard;

use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Analytics extends Controller
{
  public function index()
  {
    return view('valiant-shade.content.dashboard.dashboards-analytics');
  }
}
