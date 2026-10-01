<?php

namespace App\Models;

/**
 * GuildNews is maintained for backward compatibility.
 * All news records are stored in the canonical `news` table via App\Models\News.
 */
class GuildNews extends News
{
    protected $table = 'news';
}
