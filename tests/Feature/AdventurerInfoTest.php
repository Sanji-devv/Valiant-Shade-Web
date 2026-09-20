<?php

namespace Tests\Feature;

use App\Models\GuildBailiff;
use App\Models\GuildSetting;
use App\Models\PlayerXp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdventurerInfoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('tr');
    }

    public function test_maceraci_info_page_renders_with_seeded_excel_data(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/adventurer-info');

        $response->assertStatus(200);
        $response->assertSee('Maceracı Bilgileri');
        $response->assertSee('Ödenekler');
        $response->assertSee('Lonca Envanteri');
        $response->assertSee('Genel Sayfa');
        $response->assertSee("Bailiff's");
        $response->assertDontSee('Rank Pool & Financial Distribution Analysis');

        // Verify tabs order matches Excel from left to right:
        // 1. Maceracı Bilgileri -> 2. Ödenekler -> 3. Lonca Envanteri -> 4. Genel Sayfa
        $html = $response->getContent();
        $posRoster = strpos($html, 'id="tab-roster"');
        $posAllowances = strpos($html, 'id="tab-allowances"');
        $posInventory = strpos($html, 'id="tab-inventory"');
        $posOverview = strpos($html, 'id="tab-overview"');

        $this->assertTrue($posRoster !== false && $posAllowances !== false && $posInventory !== false && $posOverview !== false);
        $this->assertTrue($posRoster < $posAllowances);
        $this->assertTrue($posAllowances < $posInventory);
        $this->assertTrue($posInventory < $posOverview);

        // Check Guild Level 12 and 546 points from Excel
        $response->assertSee('Lonca Seviyesi');
        $response->assertSee('12');
        $response->assertSee('546');

        // Check Bailiff members
        $response->assertSee('Thoma Quickfire');
        $response->assertSee('Kahraman Kaynakları');
        $response->assertSee('Temran');
        $response->assertSee('Envanter');
        $response->assertSee('Mirang');
        $response->assertSee('Lonca Temsilcisi');
        $response->assertSee('Zandar');
        $response->assertSee('Questmaster');
        $response->assertSee('Mart');
        $response->assertSee('Arşivci');
        $response->assertSee('Yanek');
        $response->assertSee('Disiplin');
        $response->assertSee('Oathkeeper');

        // Check allowances
        $response->assertSee('991,787'); // Bart allowance
        $response->assertSee('553,770'); // Kerrik allowance

        // Verify GuildBailiff count in DB
        $this->assertEquals(7, GuildBailiff::count());

        // Verify Guild Settings in DB
        $this->assertEquals(12, GuildSetting::get('guild_level', 12));
        $this->assertEquals(546, GuildSetting::get('next_level_points', 546));
    }

    public function test_maceraci_info_alias_route_works(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/guild-overview');

        $response->assertStatus(200);
        $response->assertSee("Bailiff's");
    }

    public function test_maceraci_info_json_api_response(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->getJson('/valiant-shade/pages/maceraci-info');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'guild_level' => ['level', 'next_level_points'],
            'bailiffs',
            'rank_pools',
            'roster',
            'allowances',
            'inventory_summary',
        ]);

        $json = $response->json();
        $this->assertEquals(12, $json['guild_level']['level']);
        $this->assertEquals(546, $json['guild_level']['next_level_points']);
        $this->assertCount(7, $json['bailiffs']);
    }

    public function test_admin_can_update_character_allowance(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        // First load page to ensure seeds exist
        $this->actingAs($admin)->get('/valiant-shade/pages/maceraci-info');

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/maceraci-info/allowance', [
            'character_name' => 'Bart',
            'allowance_sp' => 1250000,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('player_xps', [
            'character_name' => 'Bart',
            'allowance_sp' => 1250000,
        ]);
    }

    public function test_admin_can_update_bailiff_officer(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        // First load page to ensure seeds exist
        $this->actingAs($admin)->get('/valiant-shade/pages/maceraci-info');

        $bailiff = GuildBailiff::where('character_name', 'Zandar')->first();
        $this->assertNotNull($bailiff);

        $response = $this->actingAs($admin)->putJson("/valiant-shade/pages/maceraci-info/bailiff/{$bailiff->id}", [
            'character_name' => 'Zandar Highwind',
            'role' => 'Head Questmaster',
            'assigned_date' => '2026-09-01',
            'status' => 'active',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('guild_bailiffs', [
            'id' => $bailiff->id,
            'character_name' => 'Zandar Highwind',
            'role' => 'Head Questmaster',
        ]);
    }

    public function test_admin_can_update_guild_level_and_points(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/maceraci-info/guild-level', [
            'guild_level' => 13,
            'next_level_points' => 700,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(13, GuildSetting::get('guild_level'));
        $this->assertEquals(700, GuildSetting::get('next_level_points'));
    }

    public function test_admin_can_reset_to_excel_defaults(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        // First seed and modify
        $this->actingAs($admin)->get('/valiant-shade/pages/maceraci-info');
        GuildSetting::set('guild_level', 99);

        // Reset
        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/maceraci-info/reset-excel');

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(12, GuildSetting::get('guild_level'));
        $this->assertEquals(546, GuildSetting::get('next_level_points'));
    }

    public function test_regular_user_cannot_update_guild_settings(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->postJson('/valiant-shade/pages/maceraci-info/guild-level', [
            'guild_level' => 15,
            'next_level_points' => 900,
        ]);

        // Expect 403 Forbidden
        $response->assertStatus(403);
    }
}
