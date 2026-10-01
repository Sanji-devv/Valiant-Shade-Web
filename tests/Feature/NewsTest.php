<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ]);
    }

    public function test_news_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/news');
        $response->assertStatus(200);
        $response->assertSee('Lonca Haberleri &amp; Duyuru Yönetimi', false);
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
}
