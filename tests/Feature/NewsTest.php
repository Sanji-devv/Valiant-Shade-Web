<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class NewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'admin@valiantshade.com',
            'name' => 'Guild Master',
            'role' => 'ADMIN',
        ]);
    }

    public function test_news_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/news');
        $response->assertStatus(200);
        $response->assertSee('Lonca Haberleri &amp; Duyuru Yönetimi', false);
        $response->assertSee('href="'.\App\Support\PublicSiteUrls::gazette('/son-sayi').'"', false);
    }

    public function test_can_create_news(): void
    {
        $payload = [
            'title' => 'Cenaze Merasimi: Düşen Kahramanlara Veda',
            'category' => 'Anma & Taziye',
            'badge_color' => 'dark',
            'category_icon' => 'icon-base ti tabler-candle',
            'tag' => 'Onur Töreni',
            'tag_icon' => 'icon-base ti tabler-shield-heart',
            'date_text' => 'Bugün',
            'description' => 'Loncamızın cesur savaşçıları şan ve şerefle anıldı.',
            'image_preset' => 'static/valiant-shade/assets/img/news/guild_heroes_funeral.jpg',
            'button_text' => 'Taziye Defteri',
            'button_url' => '/valiant-shade/pages/news',
            'button_color' => 'secondary',
            'button_icon' => 'icon-base ti tabler-heart-handshake',
            'location_badge' => 'Kahramanlar Mezarlığı',
            'order' => 1,
            'is_active' => 1,
            'stats' => [
                ['label' => 'Miras', 'value' => 'Ebedi', 'color' => 'text-warning'],
                ['label' => 'Katılım', 'value' => 'Tüm Lonca', 'color' => 'text-info'],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/valiant-shade/pages/news', $payload);
        $response->assertRedirect('/valiant-shade/pages/news');

        $this->assertDatabaseHas('news', [
            'title' => 'Cenaze Merasimi: Düşen Kahramanlara Veda',
            'category' => 'Anma & Taziye',
        ]);
    }

    public function test_admin_sees_edit_and_delete_on_database_news_cards(): void
    {
        News::create([
            'title' => 'Yönetilebilir Haber Başlığı Uzun',
            'category' => 'Lonca Haberi',
            'status' => 'published',
            'description' => 'Bu haber kartında düzenle ve sil kontrolleri görünmelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/news');
        $response->assertOk();
        $response->assertSee('openEditNewsModal', false);
        $response->assertSee('confirmDeleteNews', false);
        $response->assertSee('tabler-edit', false);
        $response->assertSee('tabler-trash', false);
    }

    public function test_reordering_news_saves_the_requested_order(): void
    {
        $attributes = [
            'category' => 'Lonca Haberi',
            'status' => 'published',
            'description' => 'Sıralama testi.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ];
        $first = News::create([...$attributes, 'title' => 'İlk haber']);
        $second = News::create([...$attributes, 'title' => 'İkinci haber']);

        $this->actingAs($this->user)
            ->postJson(route('valiant-shade.pages-news.reorder'), ['ids' => [$second->id, $first->id]])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(2, (int) $second->fresh()->order);
        $this->assertSame(1, (int) $first->fresh()->order);
    }

    public function test_editorial_reaction_is_recorded_via_news_page_route(): void
    {
        $news = News::create([
            'title' => 'Tepki testi',
            'category' => 'Lonca Haberi',
            'status' => 'published',
            'description' => 'Editör tepkisi testi.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('valiant-shade.pages-news.editorial-reaction', $news->id), ['reaction' => 'victory'])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame(1, \App\ValiantShade\Support\NewsEngagement::reactionMetrics($news->fresh()->stats)['victory']);
    }

    public function test_only_admins_see_the_publish_action_for_pending_news(): void
    {
        $pending = News::create([
            'title' => 'Bekleyen haber için onay görünürlüğü başlığı',
            'category' => 'Lonca Söylentisi',
            'status' => 'pending',
            'description' => 'Bekleyen haber gönderileri yalnızca yönetici için yayınla düğmesi göstermelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'is_active' => false,
        ]);
        $publishUrl = route('valiant-shade.pages-news.approve', $pending->id);

        $this->actingAs($this->user)
            ->get('/valiant-shade/pages/news')
            ->assertOk()
            ->assertSee($publishUrl, false);

        $member = User::factory()->create(['role' => 'PLAYER']);
        $this->actingAs($member)
            ->get('/valiant-shade/pages/news')
            ->assertOk()
            ->assertDontSee($publishUrl, false);
    }

    public function test_non_admin_with_archive_read_permission_cannot_publish_news(): void
    {
        $pending = News::create([
            'title' => 'Arşiv izniyle yayınlanmaması gereken haber',
            'category' => 'Lonca Söylentisi',
            'status' => 'pending',
            'description' => 'Arşiv okuma izni yayın onaylama yetkisi vermemelidir.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'is_active' => false,
        ]);
        $member = User::factory()->create(['role' => 'PLAYER']);
        $member->givePermissionTo(Permission::findOrCreate('archives.read', 'web'));

        $this->actingAs($member)
            ->post(route('valiant-shade.pages-news.approve', $pending->id))
            ->assertForbidden();

        $this->assertDatabaseHas('news', [
            'id' => $pending->id,
            'status' => 'pending',
            'is_active' => false,
        ]);
    }

    public function test_dashboard_displays_news_from_news_table(): void
    {
        News::create([
            'title' => 'Zafer Sancağı Dalgalanıyor',
            'category' => 'Büyük Sefer',
            'badge_color' => 'success',
            'description' => 'Kadim harabeler fethedildi.',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_heroes_funeral.jpg',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/valiant-shade');
        $response->assertStatus(200);
        $response->assertSee('guild-news-swiper');
        $response->assertSee('Zafer Sancağı Dalgalanıyor');
    

    }

    public function test_news_edit_modal_supports_custom_image_upload(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/news');
        $response->assertOk();
        $response->assertSee('name="custom_image"', false);
        $response->assertSee('multipart/form-data', false);
    }

    public function test_can_set_cover_image_back_to_auto_on_update(): void
    {
        config()->set('gazette.title_image_map', []);
        config()->set('gazette.image_library', []);
        config()->set('gazette.pf1_image_library', []);
        config()->set('gazette.default_news_images', [
            'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
        ]);

        $news = News::create([
            'title' => 'Old title',
            'category' => 'Old category',
            'status' => 'published',
            'description' => 'Old description',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_tavern_whispers.jpg',
            'is_active' => true,
        ]);

        $payload = [
            'title' => 'New title about dragons',
            'category' => 'New category',
            'description' => 'New description',
            'image_preset' => '__gazette_auto__',
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->user)->post("/valiant-shade/pages/news/{$news->id}", $payload);
        $response->assertRedirect('/valiant-shade/pages/news');

        $news->refresh();
        $this->assertNotSame('__gazette_auto__', $news->image_url);
        $this->assertNotEmpty($news->image_url);
    }
}
