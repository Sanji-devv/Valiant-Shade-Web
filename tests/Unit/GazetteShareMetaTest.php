<?php

namespace Tests\Unit;

use App\Models\News;
use App\ValiantShade\Support\GazetteShareMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class GazetteShareMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_share_meta_uses_absolute_image_and_excerpt(): void
    {
        $news = News::create([
            'title' => 'Liman yangını: iskele kapatıldı acil müdahale',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'Liman iskelesinde çıkan yangın nedeniyle bölge tahliye edildi. İtfaiye ekipleri müdahalede.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $request = Request::create('/', 'GET', ['haber' => $news->id]);
        $meta = GazetteShareMeta::forRequest($request);

        $gazetteHost = config('gazette.domain');
        $this->assertStringContainsString($gazetteHost, $meta['image']);
        $this->assertStringContainsString('guild_dragon_quest.jpg', $meta['image']);
        $this->assertStringContainsString('Lağımdan Sesler', $meta['title']);
        $this->assertStringContainsString('Liman iskelesinde', $meta['description']);
        $this->assertStringContainsString("haber={$news->id}", $meta['url']);
        $this->assertSame('article', $meta['type']);
    }
}
