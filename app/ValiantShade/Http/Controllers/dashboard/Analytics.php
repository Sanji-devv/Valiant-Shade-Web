<?php

namespace App\ValiantShade\Http\Controllers\dashboard;

use App\ValiantShade\Http\Controllers\Controller;
use App\ValiantShade\Http\Controllers\pages\NewsController;
use Illuminate\Http\Request;

class Analytics extends Controller
{
  public function index()
  {
    $news = NewsController::getActiveNews();
    $guildNews = $news;

    return view('valiant-shade.content.dashboard.dashboards-analytics', compact('news', 'guildNews'));
  }
}
