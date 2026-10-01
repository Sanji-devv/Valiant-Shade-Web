<?php

namespace App\ValiantShade\Http\Controllers\dashboard;

use App\Models\News;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class Analytics extends Controller
{
  public function index()
  {
    $guildNews = collect();
    try {
      if (Schema::hasTable('news')) {
        $guildNews = News::where('is_active', true)
          ->orderBy('order', 'asc')
          ->orderBy('created_at', 'desc')
          ->get();
      } elseif (Schema::hasTable('guild_news')) {
        $guildNews = \App\Models\GuildNews::where('is_active', true)
          ->orderBy('order', 'asc')
          ->orderBy('created_at', 'desc')
          ->get();
      }
    } catch (\Throwable $e) {
      $guildNews = collect();
    }

    return view('valiant-shade.content.dashboard.dashboards-analytics', compact('guildNews'));
  }
}
