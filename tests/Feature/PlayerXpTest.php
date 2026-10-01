<?php

namespace Tests\Feature;

use App\Models\PlayerXp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerXpTest extends TestCase
{
    use RefreshDatabase;

    public function test_xp_tracker_page_renders_with_seeded_data_and_stats(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/xp-tracker');

        $response->assertStatus(200);
        $response->assertSee('Player XP Tracker');
        $response->assertSee('Arelion Vane');
        $response->assertSee('Argus');
        $response->assertSee('Aki');
        $response->assertSee('3. Balkabağı');
        $response->assertSee('Abraham');
        $response->assertSee('Alex');
        $response->assertSee(__('Hall of Fame (KIA / MIA)'));
        $response->assertSee(__('Active Roster (Guild Members)'));

        // Verify that 64 adventurers were seeded automatically (32 active + 32 fallen)
        $this->assertEquals(64, PlayerXp::count());
        $this->assertEquals(32, PlayerXp::active()->count());
        $this->assertEquals(32, PlayerXp::fallen()->count());
    }

    public function test_xp_levels_enum_config_and_class(): void
    {
        // 1. Verify config/enums/xp_levels.php exists and contains all 21 levels (0 to 20)
        $path = config_path('enums/xp_levels.php');
        $this->assertFileExists($path);
        $enumConfig = require $path;

        $this->assertArrayHasKey('table', $enumConfig);
        $this->assertArrayHasKey('thresholds', $enumConfig);
        $this->assertArrayHasKey('failsafes', $enumConfig);

        $this->assertCount(21, $enumConfig['table']);
        $this->assertEquals(0, $enumConfig['thresholds'][0]);
        $this->assertEquals(0, $enumConfig['thresholds'][1]);
        $this->assertEquals(1300, $enumConfig['thresholds'][2]);
        $this->assertEquals(3300, $enumConfig['thresholds'][3]);
        $this->assertEquals(15000, $enumConfig['thresholds'][6]);
        $this->assertEquals(105000, $enumConfig['thresholds'][11]);
        $this->assertEquals(2400000, $enumConfig['thresholds'][20]);

        // 2. Verify config/enums/xp_level.php alias exists and returns the same data
        $aliasPath = config_path('enums/xp_level.php');
        $this->assertFileExists($aliasPath);
        $aliasConfig = require $aliasPath;
        $this->assertEquals($enumConfig['thresholds'], $aliasConfig['thresholds']);

        // 3. Verify PlayerXp model reads config/enums/xp_levels.php thresholds & failsafes
        $this->assertEquals($enumConfig['thresholds'], PlayerXp::getXpThresholds());
        $this->assertEquals($enumConfig['failsafes'], PlayerXp::getXpFailsafes());
    }

    public function test_pathfinder_fast_progression_level_calculation(): void
    {
        // Check Pathfinder 1e Fast Progression thresholds
        $this->assertEquals(1, PlayerXp::calculateLevelFromXp(0));
        $this->assertEquals(1, PlayerXp::calculateLevelFromXp(266));
        $this->assertEquals(1, PlayerXp::calculateLevelFromXp(1299));
        $this->assertEquals(2, PlayerXp::calculateLevelFromXp(1300));
        $this->assertEquals(2, PlayerXp::calculateLevelFromXp(3299));
        $this->assertEquals(3, PlayerXp::calculateLevelFromXp(3300));
        $this->assertEquals(3, PlayerXp::calculateLevelFromXp(4161));
        $this->assertEquals(4, PlayerXp::calculateLevelFromXp(6000));
        $this->assertEquals(5, PlayerXp::calculateLevelFromXp(10000));
        $this->assertEquals(6, PlayerXp::calculateLevelFromXp(15000));
        $this->assertEquals(7, PlayerXp::calculateLevelFromXp(23382));
        $this->assertEquals(8, PlayerXp::calculateLevelFromXp(43487));
        $this->assertEquals(11, PlayerXp::calculateLevelFromXp(129141));
    }

    public function test_admin_can_create_new_adventurer_with_computed_xp_and_level(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $payload = [
            'character_name' => 'Elric of Melnibone',
            'player_name' => 'Michael Moorcock',
            'rank' => 'Üye',
            'sessions_count' => 5,
            'mission_xp' => 1200,
            'report_xp' => 300,
            'rank_xp' => 0,
            'extra_xp' => 0,
            'bailiff_xp' => 0,
            'bonus_xp' => 0,
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/xp-tracker', $payload);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        // Total XP = 1200 + 300 = 1500 -> Fast Progression Level 2 (1,300 - 3,299)
        $this->assertDatabaseHas('player_xps', [
            'character_name' => 'Elric of Melnibone',
            'player_name' => 'Michael Moorcock',
            'total_xp' => 1500,
            'level' => 2,
            'rank' => 'Üye',
            'sessions_count' => 5,
        ]);
    }

    public function test_admin_can_update_adventurer(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/xp-tracker'); // Ensure seed

        $adv = PlayerXp::where('character_name', 'Aki')->first();
        $this->assertNotNull($adv);

        $payload = [
            'character_name' => 'Aki The Swift',
            'player_name' => 'Muhammed Akgül',
            'rank' => 'Uzman',
            'sessions_count' => 6,
            'mission_xp' => 1500,
            'report_xp' => 500,
            'rank_xp' => 3300,
            'extra_xp' => 1000,
            'bailiff_xp' => 0,
            'bonus_xp' => 0,
            'status' => 'active',
        ];

        $response = $this->actingAs($admin)->putJson("/valiant-shade/pages/xp-tracker/{$adv->id}", $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Total = 1500 + 500 + 3300 + 1000 = 6300 XP -> Fast Progression Level 4 (6,000 - 9,999)
        $this->assertDatabaseHas('player_xps', [
            'id' => $adv->id,
            'character_name' => 'Aki The Swift',
            'rank' => 'Uzman',
            'total_xp' => 6300,
            'level' => 4,
            'sessions_count' => 6,
        ]);
    }

    public function test_quick_xp_award_updates_total_xp_and_level(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/xp-tracker');

        // Arin has 1366 XP (Level 2). Let's award +2000 Mission XP -> 3366 XP (Level 3!)
        $arin = PlayerXp::where('character_name', 'Arin')->first();
        $this->assertNotNull($arin);
        $this->assertEquals(2, $arin->level);
        $initialSessions = $arin->sessions_count;

        $response = $this->actingAs($admin)->postJson("/valiant-shade/pages/xp-tracker/{$arin->id}/quick-xp", [
            'amount' => 2000,
            'category' => 'mission',
            'note' => 'Defeated Cave Troll',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $arin->refresh();
        $this->assertEquals(3366, $arin->total_xp);
        $this->assertEquals(3, $arin->level); // Promoted to Level 3!
        $this->assertEquals($initialSessions + 1, $arin->sessions_count); // Sessions incremented for mission XP
        $this->assertStringContainsString('Defeated Cave Troll', $arin->notes);
    }

    public function test_admin_can_change_status_to_kia_and_revive(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/xp-tracker');

        $adv = PlayerXp::where('character_name', 'Alberto')->first();
        $this->assertEquals('active', $adv->status);

        // Mark as KIA
        $kiaResp = $this->actingAs($admin)->postJson("/valiant-shade/pages/xp-tracker/{$adv->id}/status", [
            'status' => 'kia',
            'reason' => 'KIA',
        ]);
        $kiaResp->assertStatus(200);
        $adv->refresh();
        $this->assertEquals('kia', $adv->status);

        // Revive back to active
        $reviveResp = $this->actingAs($admin)->postJson("/valiant-shade/pages/xp-tracker/{$adv->id}/status", [
            'status' => 'active',
        ]);
        $reviveResp->assertStatus(200);
        $adv->refresh();
        $this->assertEquals('active', $adv->status);
    }

    public function test_non_admin_cannot_delete_or_reset(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $this->actingAs($user)->get('/valiant-shade/pages/xp-tracker');

        $adv = PlayerXp::first();

        // Delete forbidden for non-admin
        $delResp = $this->actingAs($user)->deleteJson("/valiant-shade/pages/xp-tracker/{$adv->id}");
        $delResp->assertStatus(403);

        // Reset forbidden for non-admin
        $resetResp = $this->actingAs($user)->postJson('/valiant-shade/pages/xp-tracker/reset-defaults');
        $resetResp->assertStatus(403);
    }

    public function test_admin_can_reset_to_original_data(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        PlayerXp::truncate();
        $this->assertEquals(0, PlayerXp::count());

        $resetResp = $this->actingAs($admin)->postJson('/valiant-shade/pages/xp-tracker/reset-defaults');
        $resetResp->assertStatus(200);

        $this->assertEquals(64, PlayerXp::count());
        $this->assertDatabaseHas('player_xps', [
            'character_name' => 'Arelion Vane',
            'total_xp' => 129141,
            'level' => 11,
        ]);
    }

    public function test_csv_export_returns_valid_csv(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/xp-tracker?export=csv');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Character Name', $response->getContent());
        $this->assertStringContainsString('Arelion Vane', $response->getContent());
        $this->assertStringContainsString('3. Balkabağı', $response->getContent());
    }

    public function test_admin_can_batch_award_xp_to_multiple_adventurers(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/xp-tracker');

        $adventurers = PlayerXp::active()->take(3)->get();
        $ids = $adventurers->pluck('id')->toArray();
        $initialXps = $adventurers->pluck('total_xp', 'id')->toArray();

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/xp-tracker/batch-xp', [
            'adventurer_ids' => $ids,
            'amount' => 500,
            'category' => 'mission',
            'note' => 'Guild Boss Defeated',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        foreach ($ids as $id) {
            $adv = PlayerXp::find($id);
            $this->assertEquals($initialXps[$id] + 500, $adv->total_xp);
        }
    }

    public function test_admin_can_bulk_update_status_for_multiple_adventurers(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/xp-tracker');

        $adventurers = PlayerXp::active()->take(2)->get();
        $ids = $adventurers->pluck('id')->toArray();

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/xp-tracker/bulk-status', [
            'adventurer_ids' => $ids,
            'status' => 'kia',
            'reason' => 'KIA',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        foreach ($ids as $id) {
            $adv = PlayerXp::find($id);
            $this->assertEquals('kia', $adv->status);
        }
    }

    public function test_arelion_vane_xp_bar_and_pathfinder_progression_label(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $this->actingAs($user)->get('/valiant-shade/pages/xp-tracker');

        $arelion = PlayerXp::where('character_name', 'Arelion Vane')->first();
        $this->assertNotNull($arelion);
        $this->assertEquals(129141, $arelion->total_xp);

        // Pathfinder Fast Advancement: Level 11 starts at 105,000 XP, Level 12 requires 145,000 XP
        $this->assertEquals(11, $arelion->effective_level);
        $this->assertEquals(105000, $arelion->getCurrentLevelBaseXp());
        $this->assertEquals(145000, $arelion->getNextLevelXp());

        // Exact string representation requested: "129,141 / 145,000 XP"
        $this->assertEquals('129,141 / 145,000 XP', $arelion->getXpProgressLabel());

        // Progress percentage: (129141 - 105000) / (145000 - 105000) = 24141 / 40000 = 60.4%
        $this->assertEquals(60.4, $arelion->getProgressPercentage());
        $this->assertEquals(15859, $arelion->getRemainingXpToNextLevel());
    }

    public function test_dedicated_categories_and_tabs_for_candidates_and_inactives(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $response = $this->actingAs($user)->get('/valiant-shade/pages/xp-tracker');

        $response->assertStatus(200);

        // Verify dedicated tabs & switches exist in HTML
        $response->assertSee(__('Active Roster (Guild Members)'));
        $response->assertSee(__('Candidates'));
        $response->assertSee(__('Inactives'));
        $response->assertSee(__('Hall of Fame (KIA / MIA)'));
        $response->assertSee(__('Hide Inactives'));
        $response->assertSee(__('Hide Candidates'));

        // Scopes test
        $guildMembers = PlayerXp::guildMembers()->get();
        $candidates = PlayerXp::candidates()->get();
        $inactives = PlayerXp::inactives()->get();
        $fallen = PlayerXp::fallen()->get();

        $this->assertGreaterThan(0, $guildMembers->count());
        $this->assertGreaterThan(0, $candidates->count());
        $this->assertGreaterThan(0, $inactives->count());
        $this->assertEquals(32, $fallen->count());

        // Ensure Altıgen is categorized as 'candidate'
        $altigen = PlayerXp::where('character_name', 'Altıgen')->first();
        $this->assertNotNull($altigen);
        $this->assertEquals('Aday', $altigen->rank);
        $this->assertEquals('candidate', $altigen->category);

        // Ensure Arelion Vane is categorized as 'guild'
        $arelion = PlayerXp::where('character_name', 'Arelion Vane')->first();
        $this->assertEquals('guild', $arelion->category);

        // Ensure Inactive characters have category 'inactive'
        $aeglish = PlayerXp::where('character_name', 'Aeglish')->first();
        $this->assertEquals('inactive', $aeglish->category);
    }

    public function test_rank_badges_rendered_without_emojis_or_icons(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $response = $this->actingAs($user)->get('/valiant-shade/pages/xp-tracker');

        $content = $response->getContent();

        // Ensure rank table headers and values are present
        $this->assertStringContainsString('Uzman', $content);
        $this->assertStringContainsString('Aday', $content);
        $this->assertStringContainsString('Clerk', $content);
        $this->assertStringContainsString('Üye', $content);

        // Ensure rank cells in table rows do not have <i> or icon tags
        preg_match_all('/<td class="text-center">\s*<span class="badge [^>]+ font-monospace">\s*(.*?)\s*<\/span>\s*<\/td>/s', $content, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $badgeHtml) {
            $this->assertStringNotContainsString('<i', $badgeHtml);
            $this->assertStringNotContainsString('icon-base', $badgeHtml);
        }
    }
}
