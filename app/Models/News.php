<?php

namespace App\Models;

use App\ValiantShade\Services\PublicNewsService;
use App\ValiantShade\Support\NewsSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'category',
        'news_type',
        'gazette_issue_id',
        'status',
        'badge_color',
        'category_icon',
        'tag',
        'tag_icon',
        'date_text',
        'read_time',
        'published_date',
        'description',
        'full_story',
        'testimony',
        'author',
        'author_role',
        'author_avatar',
        'image_url',
        'stats',
        'button_text',
        'button_url',
        'button_color',
        'button_icon',
        'location_badge',
        'order',
        'is_active',
        'is_breaking',
        'views_count',
    ];

    protected $casts = [
        'stats' => 'array',
        'is_active' => 'boolean',
        'is_breaking' => 'boolean',
        'order' => 'integer',
        'views_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (News $news): void {
            if ((string) ($news->status ?? '') === 'published' && ! filled($news->slug)) {
                $news->slug = NewsSlug::assignUnique((string) $news->title, $news->exists ? (int) $news->id : null);
            }
        });

        static::saved(function (News $news): void {
            app(PublicNewsService::class)->bumpCacheVersion();
            app(\App\ValiantShade\Services\GazetteIssueService::class)->attachNewsToCurrentIssue($news);
        });

        static::deleted(function (): void {
            app(PublicNewsService::class)->bumpCacheVersion();
        });
    }

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