<?php

namespace DemoVuexy\Http\Controllers\maps;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Leaflet extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.maps.maps-leaflet');
  }
}
