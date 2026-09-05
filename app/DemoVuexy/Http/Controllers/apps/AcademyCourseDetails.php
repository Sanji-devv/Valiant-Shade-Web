<?php

namespace DemoVuexy\Http\Controllers\apps;

use DemoVuexy\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AcademyCourseDetails extends Controller
{
  public function index()
  {
    return view('demo-vuexy.content.apps.app-academy-course-details');
  }
}
