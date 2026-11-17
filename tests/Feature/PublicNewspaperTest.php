<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicNewspaperTest extends TestCase
{
    use RefreshDatabase;

    public function test_gazette_alias_redirects_to_gazete(): void
    {
        $aliases = config('gazette.alias_domains', []);
        if ($aliases === []) {
            $this->markTestSkipped('gazette alias not configured');
        }

        $host = $aliases[0];
        $canonical = config('gazette.domain');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/')
            ->assertRedirect('http://'.$canonical.'/');
    }

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
            ->assertSee('property="og:description"', false)
            ->assertSee(url('/favicon.ico'), false)
            ->assertSee(config('seo.gazette.title_suffix'), false);
    }

    public function test_local_home_page_links_to_gazete_subdomain(): void
    {
        $gazetteUrl = \App\Support\PublicSiteUrls::gazette();

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.$gazetteUrl.'"', false);
    }

    public function test_gazette_page_shows_separate_pdf_and_png_download_buttons(): void
    {
        $host = config('gazette.domain');

        $response = $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/?gorunum=gazette&sayi=2&yaprak=1')
            ->assertOk()
            ->assertSee('class="vs-newspaper-page vs-layout-gazette"', false)
            ->assertSee('id="printGazetteBtn"', false)
            ->assertDontSee('id="printGazetteBtn" hidden', false)
            ->assertSee('id="downloadGazettePngBtn"', false)
            ->assertDontSee('id="downloadGazettePngBtn" hidden', false)
            ->assertSee('id="openRumorModal"', false)
            ->assertDontSee('id="openHotExtraModal"', false);
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

    public function test_gazette_image_previews_do_not_request_the_current_page_on_load(): void
    {
        $host = config('gazette.domain');

        $this->withServerVariables(['HTTP_HOST' => $host])
            ->get('http://'.$host.'/?gorunum=gazette')
            ->assertOk()
            ->assertDontSee('id="rumorImagePreview" src=""', false)
            ->assertDontSee('id="flashImagePreview" src=""', false);
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

    public function test_issue_export_returns_all_published_articles_without_incrementing_views(): void
    {
        $issue = app(\App\ValiantShade\Services\GazetteIssueService::class)->ensureDefaultIssue();
        $first = News::create([
            'title' => 'Sayıdaki İlk Haber Başlığı',
            'category' => 'Lonca Haberi',
            'news_type' => 'guild_news',
            'gazette_issue_id' => $issue->id,
            'status' => 'published',
            'description' => 'İlk haberin toplu PNG dışa aktarımında yer alması gereken metni.',
            'full_story' => 'İlk haberin tamamı.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
            'views_count' => 0,
        ]);
        $second = News::create([
            'title' => 'Sayıdaki İkinci Haber Başlığı',
            'category' => 'Lonca Söylentisi',
            'news_type' => 'guild_rumor',
            'gazette_issue_id' => $issue->id,
            'status' => 'published',
            'description' => 'İkinci haber de aynı sayının toplu PNG arşivinde bulunmalı.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'is_active' => true,
            'views_count' => 0,
        ]);
        News::create([
            'title' => 'Onay Bekleyen Haber Başlığı',
            'category' => 'Lonca Söylentisi',
            'news_type' => 'guild_rumor',
            'gazette_issue_id' => $issue->id,
            'status' => 'pending',
            'description' => 'Onaysız haber dışa aktarım listesine girmemeli.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/public/news/exports/issues/'.$issue->id);
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('issue_id', $issue->id)
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $first->id, 'full_story' => 'İlk haberin tamamı.'])
            ->assertJsonFragment(['id' => $second->id]);

        $this->assertSame(0, (int) $first->fresh()->views_count);
        $this->assertSame(0, (int) $second->fresh()->views_count);
    }

    public function test_single_article_export_does_not_increment_views(): void
    {
        $news = News::create([
            'title' => 'Tekil PNG İçin Haber Başlığı',
            'category' => 'Lonca Haberi',
            'news_type' => 'guild_news',
            'status' => 'published',
            'description' => 'Bu haber tek başına PNG olarak indirilecek.',
            'full_story' => 'Bu haberin bütün metni.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
            'views_count' => 0,
        ]);

        $this->getJson('/api/public/news/exports/articles/'.$news->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('news.id', $news->id)
            ->assertJsonPath('news.full_story', 'Bu haberin bütün metni.');

        $this->assertSame(0, (int) $news->fresh()->views_count);
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

    public function test_public_can_submit_rumor_for_upcoming_weekly_issue(): void
    {
        $service = app(\App\ValiantShade\Services\GazetteIssueService::class);
        $service->syncWeeklyIssues();
        $upcoming = $service->upcomingWeeklyIssue();
        $this->assertNotNull($upcoming);
        $this->assertFalse((bool) $upcoming->is_current);

        $response = $this->postJson('/api/public/news/submissions', [
            'title' => 'Gelecek sayı için planlanan söylenti başlığı',
            'news_type' => 'guild_rumor',
            'description' => 'Bu metin gelecek Pazar basılacak sayıya önceden gönderilir; editör onayından sonra o sayıda yer alır.',
            'gazette_issue_id' => $upcoming->id,
            'anonymous' => true,
        ]);

        $response->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('news', [
            'title' => 'Gelecek sayı için planlanan söylenti başlığı',
            'gazette_issue_id' => $upcoming->id,
            'status' => 'pending',
        ]);
    }

    public function test_gazette_archive_lists_upcoming_issue_as_editable(): void
    {
        $response = $this->getJson('/api/public/news/gazette-issues');
        $response->assertOk()->assertJsonPath('success', true);

        $upcoming = collect($response->json('data'))->firstWhere('is_upcoming', true);
        $this->assertNotNull($upcoming);
        $this->assertTrue($upcoming['is_editable']);
        $this->assertFalse($upcoming['is_current']);
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

    public function test_empty_extra_issue_is_removed_and_sequences_renumbered(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'ADMIN']);
        $weekly = \App\Models\GazetteIssue::query()->where('issue_kind', 'weekly')->orderByDesc('issue_number')->first();
        $this->assertNotNull($weekly);

        $emptyExtra = \App\Models\GazetteIssue::create([
            'issue_kind' => 'extra',
            'parent_issue_id' => $weekly->id,
            'extra_sequence' => 1,
            'issue_number' => $weekly->issue_number,
            'label' => 'Sayı '.$weekly->issue_number.'.1',
            'year' => $weekly->year,
            'month' => $weekly->month,
            'weekday' => $weekly->weekday,
            'day' => $weekly->day,
            'published_on' => now()->toDateString(),
            'is_current' => false,
            'is_locked' => true,
            'locked_at' => now(),
        ]);

        $response = $this->post('/api/public/news/flash-submissions', [
            'title' => 'İkinci flash haber başlığı uzun metin',
            'news_type' => 'local_news',
            'description' => str_repeat(
                'Tek sayfa ek sayı numarası boş yaprak silindikten sonra bir olmalı. ',
                8,
            ),
        ]);
        $pendingId = (int) $response->json('submission_id');
        $this->actingAs($user)->post(route('valiant-shade.pages-news.approve', $pendingId));

        $this->assertNull(\App\Models\GazetteIssue::query()->find($emptyExtra->id));

        $extra = \App\Models\GazetteIssue::query()
            ->where('parent_issue_id', $weekly->id)
            ->where('issue_kind', 'extra')
            ->where('extra_sequence', 1)
            ->first();
        $this->assertNotNull($extra);
        $this->assertSame($pendingId, (int) \App\Models\News::query()->where('gazette_issue_id', $extra->id)->value('id'));
    }

    public function test_public_search_matches_title_and_body_across_all_issues(): void
    {
        $weekly = \App\Models\GazetteIssue::query()->where('issue_kind', 'weekly')->orderByDesc('issue_number')->first();
        $extra = \App\Models\GazetteIssue::create([
            'issue_kind' => 'extra',
            'parent_issue_id' => $weekly?->id,
            'extra_sequence' => 9,
            'issue_number' => $weekly?->issue_number ?? 2,
            'label' => 'Sayı '.($weekly?->issue_number ?? 2).'.9',
            'year' => $weekly?->year ?? 631,
            'month' => $weekly?->month ?? 'Leos',
            'weekday' => $weekly?->weekday ?? 'Dustar',
            'day' => $weekly?->day ?? 11,
            'published_on' => now()->toDateString(),
            'is_current' => false,
            'is_locked' => true,
            'locked_at' => now(),
        ]);

        News::create([
            'title' => 'Liman yangını haber başlığı uzun metin',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'gazette_issue_id' => $weekly?->id,
            'status' => 'published',
            'description' => 'Özet metni.',
            'full_story' => 'Caspian iskelesinde gecenin sessizliğini çığlıklar böldü.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
            'stats' => ['submission' => ['gazette_slot_kind' => 'corner', 'rumuz' => 'Gece Bekçisi']],
        ]);

        News::create([
            'title' => 'Başka bir konu haber başlığı uzun',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'gazette_issue_id' => $extra->id,
            'status' => 'published',
            'description' => 'Flaş: caspian limanında duman yükseldi ve ekipler yönlendi.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
            'stats' => ['submission' => ['gazette_slot_kind' => 'flash']],
        ]);

        $response = $this->getJson('/api/public/news/bootstrap?q=caspian&per_page=24');
        $response->assertOk()->assertJsonPath('success', true);

        $titles = collect($response->json('feed.data'))->pluck('title')->all();
        $this->assertCount(2, $titles);

        $emptyExtraBootstrap = $this->getJson('/api/public/news/bootstrap?gazette_layout=1&issue_id='.$extra->id.'&q=caspian');
        $emptyExtraBootstrap->assertOk()
            ->assertJsonPath('feed.meta.sheet_kind', 'search');
        $this->assertCount(2, $emptyExtraBootstrap->json('feed.data') ?? []);
    }

    public function test_lead_submission_does_not_require_news_type(): void
    {
        $service = app(\App\ValiantShade\Services\GazetteIssueService::class);
        $service->syncWeeklyIssues();
        $weekly = \App\Models\GazetteIssue::query()
            ->where('issue_kind', 'weekly')
            ->where('is_current', true)
            ->first();
        $this->assertNotNull($weekly);

        $response = $this->postJson('/api/public/news/submissions', [
            'title' => 'Manşet deneme başlığı uzun metin',
            'description' => 'Bu kısa manşet metni slayt alanında dönecek.',
            'gazette_slot_kind' => 'lead',
            'gazette_issue_id' => $weekly->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_engagement_metrics_increment_on_show_and_share(): void
    {
        $news = News::create([
            'title' => 'Ölçüm testi haber başlığı uzun',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'Okunma ve paylaşım sayaçları artmalı.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $this->getJson('/api/public/news/'.$news->id)->assertOk();
        $news->refresh();
        $this->assertGreaterThanOrEqual(1, (int) $news->views_count);

        $this->postJson('/api/public/news/'.$news->id.'/engagement', ['metric' => 'shares'])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_public_reader_reaction_increments_count(): void
    {
        $news = News::create([
            'title' => 'Tepki testi haber başlığı uzun',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'Okuyucu tepkileri sayılmalı.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);
        $readerId = 'c4bbd849-b258-47a5-b252-b4e49f37a621';

        $this->postJson('/api/public/news/'.$news->id.'/reactions', [
            'reaction' => 'mourning',
            'reader_id' => $readerId,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reactions.mourning', 1);

        $this->postJson('/api/public/news/'.$news->id.'/reactions', [
            'reaction' => 'mourning',
            'reader_id' => $readerId,
            'previous_reaction' => 'mourning',
        ])
            ->assertOk()
            ->assertJsonPath('reactions.mourning', 1);

        $stats = $news->fresh()->stats;
        $this->assertSame(1, (int) ($stats['engagement']['reactions']['mourning'] ?? 0));
        $this->assertDatabaseHas('news_reader_reactions', [
            'news_id' => $news->id,
            'reader_hash' => hash('sha256', $readerId),
            'reaction' => 'mourning',
        ]);
    }

    public function test_public_reader_reaction_validation_returns_field_errors_without_changing_counts(): void
    {
        $news = News::create([
            'title' => 'Tepki doğrulama testi haber başlığı',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'Geçersiz tepki isteği sayaçları değiştirmemelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
            'stats' => ['engagement' => ['reactions' => ['mourning' => 3]]],
        ]);

        $this->postJson('/api/public/news/'.$news->id.'/reactions', [
            'reaction' => 'invalid',
            'reader_id' => 'not-a-uuid',
            'previous_reaction' => 'stale-reaction',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['reaction', 'reader_id', 'previous_reaction']]);

        $this->assertSame(3, (int) $news->fresh()->stats['engagement']['reactions']['mourning']);
        $this->assertDatabaseCount('news_reader_reactions', 0);
    }

    public function test_public_reader_can_replace_its_reaction(): void
    {
        $news = News::create([
            'title' => 'Tepki değiştirme testi haber başlığı uzun',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'Okuyucu tepkisini değiştirebilmelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);
        $readerId = 'f7f0d4db-d649-48a6-8a91-93bcb7b43a2f';

        $this->postJson('/api/public/news/'.$news->id.'/reactions', [
            'reaction' => 'mourning',
            'reader_id' => $readerId,
        ])->assertOk();

        $this->postJson('/api/public/news/'.$news->id.'/reactions', [
            'reaction' => 'victory',
            'reader_id' => $readerId,
            'previous_reaction' => 'mourning',
        ])
            ->assertOk()
            ->assertJsonPath('reactions.mourning', 0)
            ->assertJsonPath('reactions.victory', 1);

        $this->assertDatabaseHas('news_reader_reactions', [
            'news_id' => $news->id,
            'reader_hash' => hash('sha256', $readerId),
            'reaction' => 'victory',
        ]);
        $pills = $news->fresh()->stats['pills'];
        $this->assertContains(['value' => '1', 'label' => 'Zafer kutlandı', 'color' => 'success'], $pills);
        $this->assertNotContains(['value' => '1', 'label' => 'Yas tutuldu', 'color' => 'danger'], $pills);
    }

    public function test_public_reader_can_replace_a_reaction_saved_before_browser_tracking(): void
    {
        $news = News::create([
            'title' => 'Eski tepki değiştirme testi haber başlığı uzun',
            'category' => 'Yerel Haberler',
            'news_type' => 'local_news',
            'status' => 'published',
            'description' => 'Eski tarayıcı tepkisi de taşınabilmelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
            'stats' => ['engagement' => ['reactions' => ['mourning' => 1]]],
        ]);
        $readerId = '2aa384ea-0e91-482d-9e83-073136605d53';

        $this->postJson('/api/public/news/'.$news->id.'/reactions', [
            'reaction' => 'victory',
            'reader_id' => $readerId,
            'previous_reaction' => 'mourning',
        ])
            ->assertOk()
            ->assertJsonPath('reactions.mourning', 0)
            ->assertJsonPath('reactions.victory', 1);
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
