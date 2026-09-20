<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_quest_board_page_renders_with_medieval_quests_and_demographics(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/quest-board');

        $response->assertStatus(200);
        $response->assertSee('VALIANT SHADE LONCA GÖREV PANOSU', false);
        $response->assertSee('Panoda Asılı Görevler', false);
        $response->assertSee('Ana Görev Fermanları', false);
        $response->assertSee('Yan Görevler', false);
        $response->assertSee('Sürekli Görev / Devriye', false);

        // Verify Main Quests (Columns A, B, C)
        $response->assertSee('Adalar', false);
        $response->assertSee('Beyfendi Görevi', false);
        $response->assertSee('Gökmetali Edinmek', false);
        $response->assertSee('Lagrima Keşfi', false);
        $response->assertSee('Legrimadaki Dev', false);
        $response->assertSee('Amaranth', false);
        $response->assertSee('Nexari Hanesi Araştırması', false);
        $response->assertSee('Quinna', false);
        $response->assertSee('Rolanda Sundrake', false);
        $response->assertSee('Şeytanlardan Kurtulmak', false);
        $response->assertSee('Undine Mağara Araştırması', false);
        $response->assertSee('Uriel Summertide', false);
        $response->assertSee('Vane Ailesi Artifact Arayışı', false);
        $response->assertSee('Warlord', false);

        // Verify Character-Specific Main Quests
        $response->assertSee('Tapınakları', false);
        $response->assertSee('Desen', false);
        $response->assertSee('Cennete Yolculuk', false);
        $response->assertSee('Azariah', false);
        $response->assertSee('Kurtarma', false);
        $response->assertSee('Kerrik', false);
        $response->assertSee('Gölge Diyar', false);
        $response->assertSee('Da-ak', false);
        $response->assertSee('Thoma', false);

        // Verify Side Quests (Columns E, F)
        $response->assertSee('Şanslı Martı', false);
        $response->assertSee('Gümüşdilli Hırsız', false);
        $response->assertSee('Koricunun Kaybı', false);
        $response->assertSee('Loomun Oro', false);
        $response->assertSee('Öfkeli Güruh', false);
        $response->assertSee('Ölü Ağacı Kurutmak', false);
        $response->assertSee('Paslı Kanişler', false);
        $response->assertSee('Rovesta Krallığı', false);
        $response->assertSee('Rovestada Arabuluculuk', false);
        $response->assertSee('Wald Ormanında Madenciler', false);
        $response->assertSee('Bilinmez Sesler', false);
        $response->assertSee('Erast', false);

        // Verify Recurring Quests (Columns H, I)
        $response->assertSee('Derrington Sanat Galerisi', false);
        $response->assertSee('Greyshield Zindanı', false);
        $response->assertSee('Isenburg Devriyesi', false);
        $response->assertSee('Soygun Tatbikatı', false);

        // Verify Guild Demographics (Columns K, L)
        $response->assertSee('Uzman: 22', false);
        $response->assertSee('Kıdemli: 22', false);
        $response->assertSee('Üye: 18', false);
        $response->assertSee('Aday: 273', false);
        $response->assertSee('Clerk: 60', false);
        $response->assertSee('Gezgin: 9', false);
        $response->assertSee('Inaktif: 214', false);

        // Verify Color Legend
        $response->assertSee('Kırmızı: Görev Bitti', false);
        $response->assertSee('Mavi: Görev Askıda', false);
        $response->assertSee('Gri: İptal', false);

        // Verify Quest Action Button & absence of old text
        $response->assertSee('Görevi Talep Et', false);
        $response->assertDontSee('Görevi Üstlen', false);

        // Verify Member Request label & absence of Kişisel Ana Görev and person emoji
        $response->assertSee('Üye Talebi', false);
        $response->assertDontSee('Kişisel Ana Görev', false);
        $response->assertDontSee('👤 Desen', false);
        $response->assertSee('Desen', false);

        // Verify Aktif status and unified meta badges
        $response->assertSee('Aktif', false);
        $response->assertSee('badge-member-tag', false);
        $response->assertSee('badge-meta-location', false);
        $response->assertSee('badge-meta-reward', false);
    }

    public function test_quest_board_alias_route_works(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/gorev-listesi');

        $response->assertStatus(200);
        $response->assertSee('VALIANT SHADE LONCA GÖREV PANOSU', false);
    }

    public function test_quest_board_json_api_response(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->getJson('/valiant-shade/pages/quest-board');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'quests',
            'counts' => ['total', 'main', 'side', 'recurring', 'character', 'active', 'on_hold', 'completed'],
            'guild_info' => ['ranks', 'total_adventurers', 'status_legend'],
            'is_admin',
        ]);

        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertGreaterThanOrEqual(40, $json['counts']['total']);
        $this->assertEquals(20, $json['counts']['main']);
        $this->assertEquals(12, $json['counts']['side']);
        $this->assertEquals(8, $json['counts']['recurring']);
    }

    public function test_admin_can_pin_new_quest(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $payload = [
            'title' => 'Kara Ejderhanın Mağarası',
            'category' => 'main',
            'eligibility' => 'Kıdemli/Uzman',
            'character' => 'Lonca',
            'location' => 'Kuzey Uçurumları',
            'reward' => '30,000 XP & Ejderha Hazinesi',
            'description' => 'Kara ejderha yerleşkeleri tehdit ediyor, acil sefer ordusu kurulmalıdır.',
        ];

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-board', $payload);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);
        $response->assertJsonPath('quest.title', 'Kara Ejderhanın Mağarası');
    }

    public function test_admin_can_update_quest_status(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-board/1/status', [
            'status' => 'on_hold',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'status' => 'on_hold']);
    }

    public function test_regular_user_cannot_pin_new_quest(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $payload = [
            'title' => 'İzinsiz Görev İlanı',
            'category' => 'side',
            'eligibility' => 'Hepsi',
            'description' => 'Normal üye panoya ferman asamaz.',
        ];

        $response = $this->actingAs($user)->postJson('/valiant-shade/pages/quest-board', $payload);

        $response->assertStatus(403);
    }
}
