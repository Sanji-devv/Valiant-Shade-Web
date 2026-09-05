<?php

namespace DemoVuexy\Http\Controllers\charts;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChartJs extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.charts.charts-chartjs');
  }
}
