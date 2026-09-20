<?php

namespace App\ValiantShade\Http\Controllers\pages;

use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserProjects extends Controller
{
  public function index()
  {
    return view('valiant-shade.content.pages.pages-profile-projects');
  }
}
