<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'category',
        'badge_color',
        'category_icon',
        'tag',
        'tag_icon',
        'date_text',
        'description',
        'image_url',
        'stats',
        'button_text',
        'button_url',
        'button_color',
        'button_icon',
        'location_badge',
        'order',
        'is_active',
    ];

    protected $casts = [
        'stats' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    public function getImageAssetAttribute(): string
    {
        $url = $this->image_url;
        if (empty($url)) {
            return asset('static/valiant-shade/assets/img/news/guild_dragon_quest.jpg');
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '//')) {
            return $url;
        }
        return asset(ltrim($url, '/'));
    }
}