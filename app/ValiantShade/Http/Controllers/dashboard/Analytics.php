<?php

namespace App\ValiantShade\Http\Controllers\dashboard;

use App\Models\GuildNews;
use App\ValiantShade\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Analytics extends Controller
{
  public function index()
  {
    $guildNews = collect();
    try {
      if (\Illuminate\Support\Facades\Schema::hasTable('guild_news')) {
        $guildNews = GuildNews::where('is_active', true)
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
