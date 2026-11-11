<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicNewspaperTest extends TestCase
{
    use RefreshDatabase;

    public function test_newspaper_page_is_public_on_gazette_subdomain(): void
    {
        $host = config('gazette.domain');

        $mainSiteUrl = rtrim((string) config('app.url'), '/');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/')
            ->assertOk()
            ->assertSee('Lağımdan Sesler')
            ->assertSee('href="'.$mainSiteUrl.'"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('property="og:description"', false);
    }

    public function test_gazette_page_shows_separate_pdf_and_png_download_buttons(): void
    {
        $host = config('gazette.domain');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/?gorunum=gazette&sayi=2&yaprak=1')
            ->assertOk()
            ->assertSee('class="vs-newspaper-page vs-layout-gazette"', false)
            ->assertSee('id="printGazetteBtn"', false)
            ->assertDontSee('id="printGazetteBtn" hidden', false)
            ->assertSee('id="downloadGazettePngBtn"', false)
            ->assertDontSee('id="downloadGazettePngBtn" hidden', false)
            ->assertSee('id="openHotExtraModal"', false);
    }

    public function test_whatsapp_preview_meta_for_shared_article_link(): void
    {
        $host = config('gazette.domain');

        $news = News::create([
            'title' => 'Paylaşım önizlemesi için test haber başlığı uzun',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'WhatsApp bağlantı önizlemesinde bu özet metni ve woodcut görsel görünmelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/?haber='.$news->id)
            ->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('Paylaşım önizlemesi için test haber başlığı uzun', false)
            ->assertSee('WhatsApp bağlantı önizlemesinde bu özet metni', false)
            ->assertSee('guild_dragon_quest.jpg', false)
            ->assertSee('name="twitter:card"', false);
    }

    public function test_gazette_pages_show_separate_pdf_and_png_download_buttons(): void
    {
        $host = config('gazette.domain');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/?gorunum=gazette&sayi=2&yaprak=1')
            ->assertOk()
            ->assertSee('class="vs-newspaper-page vs-layout-gazette"', false)
            ->assertSee('id="printGazetteBtn"', false)
            ->assertDontSee('id="printGazetteBtn" hidden', false)
            ->assertSee('id="downloadGazettePngBtn"', false)
            ->assertDontSee('id="downloadGazettePngBtn" hidden', false);
    }

    public function test_main_domain_newspaper_path_redirects_to_gazette_subdomain(): void
    {
        $this->get('/newspaper')
            ->assertRedirect(route('web.newspaper'));
    }

    public function test_public_api_lists_only_published_news(): void
    {
        News::create([
            'title' => 'Yayında Olan Haber Başlığı Uzun Metin',
            'category' => 'Lonca Haberi',
            'news_type' => 'guild_news',
            'status' => 'published',
            'description' => 'Bu haber herkese açık olmalı ve yeterince uzun bir açıklama içermelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        News::create([
            'title' => 'Bekleyen Söylenti Başlığı Uzun Metin',
            'category' => 'Lonca Söylentisi',
            'news_type' => 'guild_rumor',
            'status' => 'pending',
            'description' => 'Bu metin henüz onaylanmadı ve API tarafından görülmemelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/public/news/bootstrap');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertHeader('Cache-Control', 'private, no-cache, must-revalidate');

        $titles = collect($response->json('feed.data'))->pluck('title')->all();
        $this->assertContains('Yayında Olan Haber Başlığı Uzun Metin', $titles);
        $this->assertNotContains('Bekleyen Söylenti Başlığı Uzun Metin', $titles);
    }

    public function test_public_can_submit_pending_rumor(): void
    {
        $response = $this->postJson('/api/public/news/submissions', [
            'title' => 'Gece yarısı han kapısında garip ışıklar',
            'news_type' => 'environment_rumor',
            'description' => 'Köylüler gece yarısı han kapısında mor ışıklar gördüğünü söylüyor. Detaylar henüz doğrulanmadı.',
            'anonymous' => true,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('news', [
            'title' => 'Gece yarısı han kapısında garip ışıklar',
            'status' => 'pending',
            'is_active' => false,
            'news_type' => 'environment_rumor',
        ]);
    }

    public function test_approved_news_appears_in_public_bootstrap_immediately(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'ADMIN']);

        $pending = News::create([
            'title' => 'Onay Sonrası Görünür Haber Başlığı',
            'category' => 'Lonca Söylentisi',
            'news_type' => 'guild_rumor',
            'status' => 'pending',
            'description' => 'Bu haber onaylandıktan hemen sonra public bootstrap listesinde yer almalıdır.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'is_active' => false,
        ]);

        Cache::put('public.news.cache_version', 10, now()->addHour());

        $this->actingAs($user)->post(route('valiant-shade.pages-news.approve', $pending->id))
            ->assertRedirect(route('valiant-shade.pages-news'));

        $this->assertGreaterThan(10, (int) Cache::get('public.news.cache_version'));

        $response = $this->getJson('/api/public/news/bootstrap');
        $titles = collect($response->json('feed.data'))->pluck('title')->all();
        $this->assertContains('Onay Sonrası Görünür Haber Başlığı', $titles);
    }

    public function test_flash_submission_creates_extra_issue_after_approval(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'ADMIN']);

        $response = $this->post('/api/public/news/flash-submissions', [
            'title' => 'Liman iskelesi alevlere teslim oldu haber başlığı',
            'news_type' => 'local_news',
            'description' => str_repeat(
                'Bu metin tek sayfalık flash haber ek sayısıdır ve gazete yapraklarında sütunlar halinde akar. ',
                8,
            ),
            'location_badge' => 'Isonburg limanı',
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $pendingId = (int) $response->json('submission_id');

        $this->actingAs($user)->post(route('valiant-shade.pages-news.approve', $pendingId));

        $this->assertDatabaseHas('gazette_issues', [
            'issue_kind' => 'extra',
            'extra_sequence' => 1,
        ]);

        $issueId = (int) \App\Models\GazetteIssue::query()->where('issue_kind', 'extra')->value('id');

        $bootstrap = $this->getJson('/api/public/news/bootstrap?gazette_layout=1&issue_id='.$issueId);
        $bootstrap->assertOk()
            ->assertJsonPath('feed.meta.sheet_kind', 'extra')
            ->assertJsonPath('feed.meta.last_page', 1);

        $this->assertDatabaseHas('news', [
            'id' => $pendingId,
            'gazette_issue_id' => $issueId,
        ]);

        $this->getJson('/api/public/news/bootstrap?gazette_layout=1&issue_id='.$issueId)
            ->assertOk()
            ->assertJsonPath('feed.data.0.id', $pendingId);
    }

    public function test_cache_version_bumps_after_news_change(): void
    {
        Cache::put('public.news.cache_version', 3, now()->addHour());

        News::create([
            'title' => 'Cache bump test haberi uzun başlık',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'Cache sürümü artmalı ve public API yeni sürüm anahtarını kullanmalıdır.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $this->assertSame(4, (int) Cache::get('public.news.cache_version'));
    }
}
