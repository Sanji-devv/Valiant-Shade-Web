<?php

namespace App\ValiantShade\Http\Controllers;

use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserConnections extends Controller
{
  public function index()
  {
    return view('valiant-shade.content.pages.pages-profile-connections');
  }
}
