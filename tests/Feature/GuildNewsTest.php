<?php

namespace Tests\Feature;

use App\Models\GuildNews;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuildNewsTest extends TestCase
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

    public function test_guild_news_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/guild-news');
        $response->assertStatus(200);
        $response->assertSee('Lonca Haberleri &amp; Duyuru Yönetimi', false);
    }

    public function test_can_create_guild_news(): void
    {
        $payload = [
            'title' => 'Test Sefer Zaferi Bülteni',
            'category' => 'Özel Zafer',
            'badge_color' => 'success',
            'category_icon' => 'icon-base ti tabler-trophy',
            'tag' => 'Özel Sefer',
            'tag_icon' => 'icon-base ti tabler-sparkles',
            'date_text' => 'Yeni',
            'description' => 'Test ekibi harabeleri başarıyla temizledi.',
            'image_preset' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'button_text' => 'Göz At',
            'button_url' => '/valiant-shade/pages/inventory',
            'button_color' => 'success',
            'button_icon' => 'icon-base ti tabler-arrow-right',
            'location_badge' => 'Kuzey Harabeleri',
            'order' => 99,
            'is_active' => 1,
            'stats' => [
                ['label' => 'XP', 'value' => '+5k', 'color' => 'text-warning'],
                ['label' => 'Kasa', 'value' => '1000 GP', 'color' => 'text-success'],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/valiant-shade/pages/guild-news', $payload);
        $response->assertRedirect('/valiant-shade/pages/guild-news');

        $this->assertDatabaseHas('guild_news', [
            'title' => 'Test Sefer Zaferi Bülteni',
            'category' => 'Özel Zafer',
        ]);
    }

    public function test_can_update_guild_news(): void
    {
        $news = GuildNews::create([
            'title' => 'Eski Başlık',
            'category' => 'Duyuru',
            'badge_color' => 'primary',
            'description' => 'Eski açıklama',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'order' => 1,
            'is_active' => true,
        ]);

        $updatePayload = [
            'title' => 'Güncellenmiş Yeni Başlık',
            'category' => 'Güncel Duyuru',
            'badge_color' => 'danger',
            'category_icon' => 'icon-base ti tabler-flame',
            'description' => 'Güncellenmiş yeni açıklama',
            'image_preset' => 'static/valiant-shade/assets/img/news/guild_shadow_realm.jpg',
            'button_color' => 'danger',
            'order' => 2,
            'is_active' => 1,
        ];

        $response = $this->actingAs($this->user)->post("/valiant-shade/pages/guild-news/{$news->id}", $updatePayload);
        $response->assertRedirect('/valiant-shade/pages/guild-news');

        $this->assertDatabaseHas('guild_news', [
            'id' => $news->id,
            'title' => 'Güncellenmiş Yeni Başlık',
            'category' => 'Güncel Duyuru',
        ]);
    }

    public function test_can_toggle_guild_news_active(): void
    {
        $news = GuildNews::create([
            'title' => 'Aktiflik Testi',
            'category' => 'Test',
            'badge_color' => 'info',
            'description' => 'Test',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson("/valiant-shade/pages/guild-news/{$news->id}/toggle");
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);

        $this->assertDatabaseHas('guild_news', [
            'id' => $news->id,
            'is_active' => false,
        ]);
    }

    public function test_can_delete_guild_news(): void
    {
        $news = GuildNews::create([
            'title' => 'Silinecek Haber',
            'category' => 'Test',
            'badge_color' => 'secondary',
            'description' => 'Silinecek',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/valiant-shade/pages/guild-news/{$news->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('guild_news', [
            'id' => $news->id,
        ]);
    }

    public function test_dashboard_displays_active_guild_news(): void
    {
        GuildNews::create([
            'title' => 'Manşet Zafer Haberi',
            'category' => 'Zafer',
            'badge_color' => 'warning',
            'description' => 'Manşet açıklama',
            'image_url' => 'static/valiant-shade/assets/img/news/guild_dragon_quest.jpg',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/valiant-shade');
        $response->assertStatus(200);
        $response->assertSee('guild-news-swiper');
        $response->assertSee('Manşet Zafer Haberi');
    }
}
