<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Quest;
use App\Models\Character;
use App\Models\PlayerXp;
use App\ValiantShade\Services\QuestReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuestReportsRequirementsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected QuestReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = app(QuestReportService::class);
        $this->service->resetToDefaults();
    }

    public function test_all_user_requirements_present_in_page_dom(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/quest-reports');
        $response->assertStatus(200);

        $html = $response->getContent();

        // 1. Hızlı Kadroya Ekle/Çıkar: REMOVED
        $this->assertStringNotContainsString('quickAdventurersChipsContainer', $html);
        $this->assertStringNotContainsString('quickEditAdventurersChipsContainer', $html);
        $this->assertStringNotContainsString('Hızlı Kadroya Ekle', $html);

        // 2. Hızlı Senaryolar: REMOVED
        $this->assertStringNotContainsString('btnPresetNormal1', $html);
        $this->assertStringNotContainsString('btnPresetEpic3', $html);
        $this->assertStringNotContainsString('btnPresetDepoPen', $html);
        $this->assertStringNotContainsString('btnPresetReset', $html);
        $this->assertStringNotContainsString('Hızlı Senaryolar', $html);

        // 3. Level (D) Görev Seviyesi: READONLY & Auto Badge
        $this->assertStringContainsString('id="addReportLevel"', $html);
        $this->assertStringContainsString('id="addReportLevelBadge"', $html);
        $this->assertStringContainsString('readonly', $html);

        // 4. Depo Durumu (F): READONLY, Görev Sonucu (F) controls it
        $this->assertStringContainsString('id="addReportStatus"', $html);
        $this->assertStringContainsString('id="addReportDepoDisplay"', $html);
        $this->assertStringContainsString('id="addReportDepoStatus"', $html);

        // 5. Tarih: Gün/Ay/Yıl (type="date")
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('id="addReportDate"', $html);

        // 6. Rapor Kesintisi (I): XP/0.1 (%10 fix, readonly)
        $this->assertStringContainsString('id="addReportRaporCut"', $html);
        $this->assertStringContainsString('%10 Fix', $html);

        // 7. Game Master (GM) *
        $this->assertStringContainsString('id="addReportGm"', $html);
        $this->assertStringContainsString('name="gm"', $html);
    }

    public function test_create_report_with_date_and_auto_depo_and_fixed_rapor_cut(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        // Test Success report
        $payloadSuccess = [
            'title' => 'Test Seferi Başarılı',
            'date' => '2026-09-28',
            'mq' => 'none',
            'level' => 2, // Total XP = 2 * 250 + 500 = 1000
            'status' => 'success',
            'depo_status' => '1',
            'rapor_cut' => 100, // 10% of 1000
            'extra_reward' => 0,
            'gm' => 'Shalvaz',
            'reporter' => 'Tolrun',
            'other_players' => ['Veigar', 'Shantar', 'Allerion'], // 4 players total
        ];

        $responseSuccess = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payloadSuccess);
        $responseSuccess->assertStatus(200);
        $responseSuccess->assertJsonPath('success', true);
        $responseSuccess->assertJsonPath('report.total_xp', 1000);
        $responseSuccess->assertJsonPath('report.guild_cut', 50); // 5% of 1000
        $responseSuccess->assertJsonPath('report.depo_cut', 0); // Success -> 0 penalty
        $responseSuccess->assertJsonPath('report.rapor_cut', 100); // 10% of 1000
        $responseSuccess->assertJsonPath('report.date', '28/09/2026'); // Formatted to d/m/Y
        // Net pool = 1000 - 50 - 0 - 100 = 850. Avg = 850 / 4 = 212.50
        $responseSuccess->assertJsonPath('report.avg_reward', 212.5);

        // Test Failed report (depo_cut = 20% penalty)
        $payloadFailed = [
            'title' => 'Test Seferi Başarısız',
            'date' => '2026-09-28',
            'mq' => 'none',
            'level' => 2, // Total XP = 1000
            'status' => 'failed',
            'depo_status' => '0',
            'rapor_cut' => 100,
            'extra_reward' => 0,
            'gm' => 'Shalvaz',
            'reporter' => 'Tolrun',
            'other_players' => ['Veigar'], // 2 players total
        ];

        $responseFailed = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payloadFailed);
        $responseFailed->assertStatus(200);
        $responseFailed->assertJsonPath('success', true);
        $responseFailed->assertJsonPath('report.depo_cut', 200); // 20% penalty of 1000
        $responseFailed->assertJsonPath('report.rapor_cut', 100);
        // Net pool = 1000 - 50 - 200 - 100 = 650. Avg = 650 / 2 = 325
        $responseFailed->assertJsonPath('report.avg_reward', 325);
    }

    public function test_new_report_briefing_fields_start_empty(): void
    {
        $html = $this->actingAs($this->user)->get('/valiant-shade/pages/quest-reports')
            ->assertOk()->getContent();

        $document = new \DOMDocument;
        @$document->loadHTML($html);

        $this->assertSame('', $document->getElementById('addReportLocation')->getAttribute('value'));
        $this->assertSame('', $document->getElementById('addReportEnemies')->getAttribute('value'));
        $this->assertSame('', $document->getElementById('addReportFullText')->textContent);
    }

    public function test_report_briefing_can_be_saved_preserved_and_cleared(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $payload = [
            'title' => 'Briefing Test',
            'date' => '2026-09-28',
            'level' => 2,
            'gm' => 'Shalvaz',
            'reporter' => 'Tolrun',
        ];

        $created = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)
            ->assertOk()
            ->assertJsonPath('report.location', '')
            ->assertJsonPath('report.enemies', '')
            ->assertJsonPath('report.report_summary', '')
            ->assertJsonPath('report.full_report', '');

        $url = '/valiant-shade/pages/quest-reports/'.$created->json('report.id');
        $briefing = [
            'location' => 'Harken',
            'enemies' => 'Bandits',
            'report_summary' => 'Summary',
            'full_report' => 'Expedition notes',
        ];
        $this->actingAs($admin)->putJson($url, array_merge($payload, $briefing))->assertOk();
        $this->actingAs($admin)->putJson($url, $payload)
            ->assertOk()
            ->assertJsonPath('report.location', 'Harken')
            ->assertJsonPath('report.enemies', 'Bandits')
            ->assertJsonPath('report.report_summary', 'Summary')
            ->assertJsonPath('report.full_report', 'Expedition notes');

        $this->actingAs($admin)->putJson($url, array_merge($payload, array_fill_keys(array_keys($briefing), '')))
            ->assertOk()
            ->assertJsonPath('report.location', '')
            ->assertJsonPath('report.enemies', '')
            ->assertJsonPath('report.report_summary', '')
            ->assertJsonPath('report.full_report', '');

        $this->actingAs($admin)->getJson($url)
            ->assertOk()
            ->assertJsonPath('report.location', '')
            ->assertJsonPath('report.enemies', '')
            ->assertJsonPath('report.report_summary', '')
            ->assertJsonPath('report.full_report', '');
    }

    public function test_party_level_rounds_up_and_awards_are_reconciled_without_double_distribution(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        foreach (['Level Ten' => 71000, 'Level Eleven' => 105000] as $name => $xp) {
            $level = $xp === 71000 ? 10 : 11;
            DB::table('characters')->insert([
                'user_id' => $admin->id, 'name' => $name, 'current_level' => $level,
                'quest_xp' => $xp, 'total_xp' => $xp,
            ]);
            DB::table('player_xps')->insert([
                'character_name' => $name, 'level' => $level, 'sessions_count' => 3,
                'mission_xp' => $xp, 'total_xp' => $xp,
            ]);
        }

        $payload = [
            'title' => 'Automatic Party Level', 'date' => '2026-11-16', 'mq' => 'none',
            'level' => 1, 'auto_party_level' => true, 'gm' => 'GM',
            'reporter' => 'Level Ten', 'other_players' => ['Level Eleven'],
            'distribute_xp_auto' => true,
        ];
        $created = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)
            ->assertOk()->assertJsonPath('report.level', 11)->assertJsonPath('report.total_xp', 3250)
            ->assertJsonPath('distribution.xp_per_player', 1381);
        $id = $created->json('report.id');
        $questId = $created->json('report.quest_id');
        $this->assertDatabaseHas('quest_report_xp_entries', [
            'quest_id' => $questId, 'participant_name' => 'Level Ten', 'state' => 'distributed',
            'quest_xp' => 1381, 'report_xp' => 325,
        ]);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/xp-tracker/link-reports')->assertOk();
        $this->assertDatabaseHas('quest_report_xp_entries', [
            'quest_id' => $questId, 'participant_name' => 'Level Ten', 'state' => 'distributed',
            'quest_xp' => 1381, 'report_xp' => 325,
        ]);

        $this->assertDatabaseHas('player_xps', ['character_name' => 'Level Ten', 'mission_xp' => 72381, 'report_xp' => 325, 'total_xp' => 72706]);
        $this->assertDatabaseHas('characters', ['name' => 'Level Eleven', 'quest_xp' => 106381, 'total_xp' => 106381]);
        $this->actingAs($admin)->postJson("/valiant-shade/pages/quest-reports/{$id}/distribute-xp")->assertStatus(400);

        $this->actingAs($admin)->putJson("/valiant-shade/pages/quest-reports/{$id}", $payload)
            ->assertOk()->assertJsonPath('report.level', 11);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Level Ten', 'mission_xp' => 72381, 'report_xp' => 325, 'total_xp' => 72706]);
        $this->assertDatabaseHas('characters', ['name' => 'Level Eleven', 'quest_xp' => 106381, 'total_xp' => 106381]);

        $payload['other_players'] = ['New Adventurer'];
        $this->actingAs($admin)->putJson("/valiant-shade/pages/quest-reports/{$id}", $payload)
            ->assertOk()->assertJsonPath('report.level', 6)->assertJsonPath('report.total_xp', 2000);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Level Eleven', 'mission_xp' => 105000, 'total_xp' => 105000]);
        $this->assertDatabaseHas('characters', ['name' => 'Level Eleven', 'quest_xp' => 105000, 'total_xp' => 105000]);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Level Ten', 'mission_xp' => 71850, 'report_xp' => 200, 'total_xp' => 72050]);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'New Adventurer', 'player_name' => 'Unknown 16.11.2026 1', 'mission_xp' => 850]);
        $this->assertDatabaseMissing('quest_report_xp_entries', ['quest_id' => $questId, 'participant_name' => 'Level Eleven']);
        $this->assertDatabaseHas('quest_report_xp_entries', ['quest_id' => $questId, 'participant_name' => 'New Adventurer', 'state' => 'distributed']);

        $this->actingAs($admin)->deleteJson("/valiant-shade/pages/quest-reports/{$id}")->assertOk();
        $this->assertDatabaseMissing('quest_report_xp_distributions', ['quest_id' => $created->json('report.quest_id')]);
        $this->assertDatabaseMissing('quest_report_xp_entries', ['quest_id' => $questId]);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Level Ten', 'mission_xp' => 71000, 'report_xp' => 0, 'total_xp' => 71000]);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'New Adventurer', 'mission_xp' => 0, 'total_xp' => 0]);
    }

    public function test_unknown_players_are_created_once_and_receive_distinct_placeholder_names(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $payload = [
            'title' => 'Unknown Party', 'date' => '2026-11-16', 'mq' => 'none',
            'level' => 1, 'auto_party_level' => true, 'gm' => 'GM',
            'reporter' => 'Lorenzo', 'other_players' => ['Another'],
            'distribute_xp_auto' => true,
        ];
        $created = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)
            ->assertOk()->assertJsonPath('distribution.total_awarded_count', 2);

        $this->assertDatabaseHas('player_xps', ['character_name' => 'Lorenzo', 'player_name' => 'Unknown 16.11.2026 1', 'mission_xp' => 319, 'report_xp' => 75]);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Another', 'player_name' => 'Unknown 16.11.2026 2', 'mission_xp' => 319]);
        $owners = [];
        foreach (['Lorenzo' => 'Unknown 16.11.2026 1', 'Another' => 'Unknown 16.11.2026 2'] as $name => $ownerName) {
            $character = Character::where('name', $name)->firstOrFail();
            $player = PlayerXp::where('character_name', $name)->firstOrFail();
            $owner = $character->user;
            $owners[] = $owner->id;
            $this->assertSame($owner->id, $player->user_id);
            $this->assertSame($ownerName, $owner->display_name);
            $this->assertSame(0, $owner->status);
            $this->assertNull($owner->password);
            $this->assertNull($owner->password_hash);
            $this->assertSame((float) $player->total_xp, $character->total_xp);
            $this->assertDatabaseHas('quest_participants', [
                'quest_id' => $created->json('report.quest_id'), 'character_id' => $character->id,
            ]);
        }
        $this->assertNotSame($owners[0], $owners[1]);
        $this->assertDatabaseHas('quests', [
            'id' => $created->json('report.quest_id'),
            'reporter_character_id' => Character::where('name', 'Lorenzo')->value('id'),
        ]);
        $payload['title'] = 'Another Unknown Party';
        $payload['date'] = '2026-11-17';
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)->assertOk();
        $this->assertSame(2, DB::table('player_xps')->count());
        $this->assertDatabaseCount('characters', 2);
        $this->assertSame($owners, Character::whereIn('name', ['Lorenzo', 'Another'])->orderBy('id')->pluck('user_id')->all());

        auth()->logout();
        $this->post('/valiant-shade/auth/login-basic', [
            'email-username' => User::findOrFail($owners[0])->username, 'password' => 'password123',
        ])->assertSessionHasErrors('email-username');
        $this->assertGuest();
    }

    public function test_unknown_owner_names_do_not_collide_with_existing_user_names(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        User::factory()->create(['name' => 'Unknown 16.11.2026 1']);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', [
            'title' => 'Name Collision', 'date' => '2026-11-16', 'level' => 1,
            'gm' => 'GM', 'reporter' => 'New Hero', 'distribute_xp_auto' => true,
        ])->assertOk();

        $character = Character::where('name', 'New Hero')->firstOrFail();
        $this->assertSame('Unknown 16.11.2026 2', $character->user->display_name);
    }

    public function test_future_report_links_existing_tracker_without_losing_xp_and_leaves_other_orphans_alone(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $owner = User::factory()->create();
        DB::table('player_xps')->insert([
            'character_name' => 'Prior Orphan', 'player_name' => 'Earlier', 'level' => 3,
            'sessions_count' => 4, 'mission_xp' => 1000, 'report_xp' => 300, 'total_xp' => 5000,
        ]);
        DB::table('player_xps')->insert([
            'character_name' => 'Known Owner', 'user_id' => $owner->id, 'level' => 1,
            'mission_xp' => 100, 'total_xp' => 100,
        ]);
        DB::table('player_xps')->insert([
            'character_name' => 'Unchanged Orphan', 'mission_xp' => 100, 'total_xp' => 100,
        ]);
        $payload = [
            'title' => 'Existing Tracker', 'date' => '2026-11-16', 'level' => 1,
            'gm' => 'GM', 'reporter' => 'Prior Orphan', 'other_players' => ['Known Owner'],
            'distribute_xp_auto' => false,
        ];
        $created = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)->assertOk();
        $this->assertDatabaseHas('quest_report_xp_entries', [
            'quest_id' => $created->json('report.quest_id'), 'participant_name' => 'Prior Orphan', 'state' => 'pending',
        ]);
        $character = Character::where('name', 'Prior Orphan')->firstOrFail();
        $player = PlayerXp::where('character_name', 'Prior Orphan')->firstOrFail();
        $this->assertSame($character->user_id, $player->user_id);
        $this->assertSame('Earlier', $player->player_name);
        $this->assertSame(5000.0, $character->total_xp);
        $this->assertSame(3, $character->current_level);
        $this->assertSame(4, $player->sessions_count);
        $this->assertDatabaseHas('characters', ['name' => 'Known Owner', 'user_id' => $owner->id, 'total_xp' => 100]);
        $this->assertDatabaseMissing('characters', ['name' => 'Unchanged Orphan']);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Unchanged Orphan', 'user_id' => null]);

        $url = '/valiant-shade/pages/quest-reports/'.$created->json('report.id');
        $this->actingAs($admin)->postJson($url.'/distribute-xp')->assertOk();
        $this->assertDatabaseHas('quest_report_xp_entries', [
            'quest_id' => $created->json('report.quest_id'), 'participant_name' => 'Prior Orphan', 'state' => 'distributed',
        ]);
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'total_xp' => 5394]);
        $this->actingAs($admin)->deleteJson($url)->assertOk();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'total_xp' => 5000, 'quest_xp' => 1000, 'report_xp' => 300]);
        $this->assertDatabaseHas('player_xps', ['id' => $player->id, 'total_xp' => 5000, 'sessions_count' => 4]);
    }

    public function test_manual_depot_cut_changes_award_and_cannot_exceed_pool(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $payload = [
            'title' => 'Manual Depot', 'date' => '2026-11-16', 'mq' => 'none',
            'level' => 1, 'auto_party_level' => true, 'gm' => 'GM',
            'reporter' => 'Lorenzo', 'depo_cut_override' => 100,
            'distribute_xp_auto' => true,
        ];
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)
            ->assertOk()->assertJsonPath('report.depo_cut', 100)
            ->assertJsonPath('distribution.xp_per_player', 538);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Lorenzo', 'mission_xp' => 538]);

        $payload['title'] = 'Invalid Depot';
        $payload['depo_cut_override'] = 10000;
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('depo_cut_override');
    }

    public function test_untracked_legacy_report_warns_and_does_not_change_xp(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        DB::table('player_xps')->insert([
            'character_name' => 'Tolrun', 'mission_xp' => 4000, 'total_xp' => 4000, 'level' => 3,
        ]);
        $report = $this->service->getReportById(1);
        $this->actingAs($admin)->putJson('/valiant-shade/pages/quest-reports/1', [
            'title' => $report['title'], 'date' => '2026-11-16', 'level' => 1,
            'gm' => 'GM', 'reporter' => 'Tolrun',
        ])->assertOk()->assertJsonPath(
            'xp_warning', 'Bu eski raporun XP dağıtımı doğrulanamadığı için karakter XP’leri değiştirilmedi.'
        );
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Tolrun', 'mission_xp' => 4000, 'total_xp' => 4000]);
    }

    public function test_duplicate_names_and_award_failures_do_not_partially_save_a_report(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $payload = [
            'title' => 'Invalid Party', 'date' => '2026-11-16', 'mq' => 'none',
            'level' => 1, 'auto_party_level' => true, 'gm' => 'GM',
            'reporter' => 'Lorenzo', 'other_players' => ['lorenzo'],
            'distribute_xp_auto' => true,
        ];
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('reporter');
        $this->assertDatabaseCount('quests', 0);
        $this->assertDatabaseCount('player_xps', 0);
        $this->assertCount(32, $this->service->getAllReportsData());

        DB::table('player_xps')->insert([
            ['character_name' => 'Repeated'],
            ['character_name' => 'Repeated'],
        ]);
        $payload['other_players'] = ['Repeated'];
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('reporter');
        $this->assertDatabaseCount('quests', 0);
        $this->assertDatabaseMissing('player_xps', ['character_name' => 'Lorenzo']);
        $this->assertCount(32, $this->service->getAllReportsData());
    }

    public function test_failed_archive_write_rolls_back_automatic_xp_distribution(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'quest-report-');
        $this->assertNotFalse($path);
        file_put_contents($path, '[]');
        $service = new class($path) extends QuestReportService
        {
            public function __construct(private string $path) {}

            protected function getStoragePath(): string
            {
                return $this->path;
            }

            protected function saveReportsData(array $reports): void
            {
                file_put_contents($this->path, 'partial write');
                throw new \RuntimeException('Archive write failed');
            }
        };

        try {
            try {
                $service->createReport([
                    'title' => 'Rollback', 'date' => '2026-11-16', 'level' => 1,
                    'gm' => 'GM', 'reporter' => 'Lorenzo', 'distribute_xp_auto' => true,
                ]);
                $this->fail('The failed archive write must be reported.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Archive write failed', $e->getMessage());
            }
            $this->assertSame('[]', file_get_contents($path));
            $this->assertDatabaseCount('quests', 0);
            $this->assertDatabaseCount('player_xps', 0);
            $this->assertDatabaseCount('characters', 0);
            $this->assertDatabaseMissing('users', ['name' => 'Unknown 16.11.2026 1']);
            $this->assertDatabaseCount('quest_report_xp_distributions', 0);
            $this->assertDatabaseCount('quest_report_xp_entries', 0);
        } finally {
            unlink($path);
            unlink($path.'.lock');
        }
    }

    public function test_historical_report_links_are_idempotent_and_preserve_existing_xp(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $knownId = DB::table('characters')->insertGetId([
            'user_id' => $owner->id, 'name' => 'Known Hero', 'quest_xp' => 900, 'total_xp' => 1000,
        ]);
        DB::table('characters')->insert([
            ['user_id' => $owner->id, 'name' => 'Ambiguous Hero'],
            ['user_id' => $otherOwner->id, 'name' => 'Ambiguous Hero'],
        ]);
        $xpId = DB::table('player_xps')->insertGetId([
            'character_name' => 'Known Hero', 'player_name' => 'Unknown 01.01.2020 1',
            'mission_xp' => 900, 'report_xp' => 100, 'total_xp' => 1000,
        ]);
        $this->service->importReports([[
            'Görev adı' => 'Historical Link Test', 'Tarih' => '2020-01-01',
            'Raporlayan' => 'Known Hero', 'Diğer Oyuncular' => ['Ambiguous Hero', 'Missing Hero'],
            'XP' => 750, 'Rapor' => 75, 'Ortalama' => 212.5,
        ]]);
        $report = collect($this->service->getAllReportsData())->firstWhere('title', 'Historical Link Test');
        $questId = $report['quest_id'];
        $this->assertDatabaseHas('quest_report_xp_entries', [
            'quest_id' => $questId, 'participant_name' => 'Known Hero', 'character_id' => $knownId,
            'player_xp_id' => $xpId, 'state' => 'historical', 'quest_xp' => 212.5, 'report_xp' => 75,
        ]);
        $this->assertDatabaseMissing('quest_participants', ['quest_id' => $questId, 'character_id' => DB::table('characters')->where('name', 'Ambiguous Hero')->value('id')]);

        DB::table('quest_report_xp_entries')->where('quest_id', $questId)->delete();
        $url = '/valiant-shade/pages/xp-tracker/link-reports';
        $this->actingAs($this->user)->postJson($url)->assertForbidden();
        for ($i = 0; $i < 2; $i++) {
            $response = $this->actingAs($admin)->postJson($url)->assertOk()
                ->assertJsonPath('reports', 1)->assertJsonPath('entries', 3);
            $this->assertEqualsCanonicalizing(['Ambiguous Hero', 'Missing Hero'], array_column($response->json('unresolved'), 'name'));
            $this->assertDatabaseCount('quest_report_xp_entries', 3);
            $this->assertDatabaseHas('quest_report_xp_entries', [
                'quest_id' => $questId, 'participant_name' => 'Ambiguous Hero', 'character_id' => null,
                'player_xp_id' => null, 'state' => 'historical',
            ]);
            $this->assertDatabaseHas('characters', ['id' => $knownId, 'quest_xp' => 900, 'total_xp' => 1000]);
            $this->assertDatabaseHas('player_xps', ['id' => $xpId, 'mission_xp' => 900, 'report_xp' => 100, 'total_xp' => 1000]);
        }

        $this->actingAs($admin)->putJson('/valiant-shade/pages/quest-reports/'.$report['id'], [
            'title' => 'Updated Historical Link', 'date' => '2020-01-01', 'level' => 1,
            'gm' => 'GM', 'reporter' => 'Known Hero', 'other_players' => ['Missing Hero'],
        ])->assertOk();
        $this->assertDatabaseMissing('quest_report_xp_entries', ['quest_id' => $questId, 'participant_name' => 'Ambiguous Hero']);
        $this->assertDatabaseHas('player_xps', ['id' => $xpId, 'total_xp' => 1000]);
        $this->actingAs($admin)->deleteJson('/valiant-shade/pages/quest-reports/'.$report['id'])->assertOk();
        $this->assertDatabaseMissing('quest_report_xp_entries', ['quest_id' => $questId]);
        $this->assertDatabaseHas('characters', ['id' => $knownId, 'total_xp' => 1000]);
    }

    public function test_historical_linking_rolls_back_if_a_later_report_fails(): void
    {
        $this->service->importReports([
            ['Görev adı' => 'Rollback One', 'Tarih' => '2020-01-01', 'Raporlayan' => 'First'],
            ['Görev adı' => 'Rollback Two', 'Tarih' => '2020-01-02', 'Raporlayan' => 'Second'],
        ]);
        DB::table('quest_report_xp_entries')->delete();
        $linker = new class extends \App\ValiantShade\Services\QuestReportXpLinkService
        {
            private int $calls = 0;

            public function sync(Quest $quest, array $report, string $state, array $indexes, array $awards = []): array
            {
                $result = parent::sync($quest, $report, $state, $indexes, $awards);
                if (++$this->calls === 2) {
                    throw new \RuntimeException('Linking failed');
                }

                return $result;
            }
        };
        $this->app->instance(\App\ValiantShade\Services\QuestReportXpLinkService::class, $linker);

        try {
            $this->service->linkHistoricalXp();
            $this->fail('The failed link operation must be reported.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Linking failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('quest_report_xp_entries', 0);
    }

    public function test_historical_linking_skips_ambiguous_quests(): void
    {
        $this->service->importReports([[
            'Görev adı' => 'Ambiguous Quest', 'Tarih' => '2020-01-01', 'Raporlayan' => 'Hero',
        ]]);
        $reports = $this->service->getAllReportsData();
        $index = collect($reports)->search(fn ($report) => $report['title'] === 'Ambiguous Quest');
        $reportId = $reports[$index]['id'];
        unset($reports[$index]['quest_id']);
        file_put_contents(storage_path('app/quest_reports_testing.json'), json_encode($reports, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        Quest::create(['title' => 'Ambiguous Quest', 'quest_date' => '2020-01-01']);
        DB::table('quest_report_xp_entries')->delete();

        $result = $this->service->linkHistoricalXp();

        $this->assertContains($reportId, $result['skipped']);
        $this->assertDatabaseCount('quest_report_xp_entries', 0);
    }

    public function test_missing_report_xp_migration_returns_actionable_error_without_changes(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $payload = [
            'title' => 'Migration Check', 'date' => '2020-01-01', 'level' => 1,
            'gm' => 'GM', 'reporter' => 'Hero',
        ];
        $created = $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports', $payload)->assertOk();
        $url = '/valiant-shade/pages/quest-reports/'.$created->json('report.id');
        Schema::drop('quest_report_xp_entries');

        $message = 'Run database migrations before using quest report XP.';
        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->postJson('/valiant-shade/pages/xp-tracker/link-reports')->assertUnprocessable()
            ->assertJsonPath('errors.xp.0', $message);
        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->putJson($url, $payload)->assertUnprocessable()->assertJsonPath('errors.xp.0', $message);
        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->deleteJson($url)->assertUnprocessable()->assertJsonPath('errors.xp.0', $message);
        $this->assertNotNull($this->service->getReportById($created->json('report.id')));
        $this->assertDatabaseHas('quests', ['id' => $created->json('report.quest_id'), 'deleted_at' => null]);
    }

    public function test_resetting_xp_records_keeps_historical_report_snapshots_without_dangling_links(): void
    {
        $xpId = DB::table('player_xps')->insertGetId(['character_name' => 'Archived Hero']);
        $this->service->importReports([[
            'Görev adı' => 'Snapshot Reset', 'Tarih' => '2020-01-01', 'Raporlayan' => 'Archived Hero',
        ]]);
        $this->assertDatabaseHas('quest_report_xp_entries', ['player_xp_id' => $xpId]);

        app(\App\ValiantShade\Services\PlayerXpService::class)->resetToDefaults();

        $this->assertDatabaseHas('quest_report_xp_entries', [
            'participant_name' => 'Archived Hero', 'player_xp_id' => null, 'state' => 'historical',
        ]);
    }

    public function test_json_import_persists_reports_and_delete_button_route_works(): void
    {
        $page = $this->actingAs($this->user)->withSession(['locale' => 'en'])->get('/valiant-shade/pages/quest-reports');
        $page->assertSee('btnImportQuestReports');
        $page->assertSee('Import Data');
        $page->assertSee('Rapor Sil');
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $json = json_encode([[
            'Görev adı' => 'JSON İçe Aktarma Test Görevi',
            'Tarih' => '2026-10-12',
            'MQ' => '✗',
            'Level' => 1,
            'XP' => 750,
            'column_F' => '🗸',
            'Lonca' => 37.5,
            'Depo' => 0,
            'Rapor' => 75,
            'Ek' => 0,
            'Ortalama' => 637.5,
            'Oyuncu' => 1,
            'GM' => 'Import Test GM',
            'Raporlayan' => 'Import Test Reporter',
            'Diğer Oyuncular' => [],
        ]], JSON_UNESCAPED_UNICODE);

        $this->actingAs($this->user)->postJson(
            '/valiant-shade/pages/quest-reports/import',
            ['file' => UploadedFile::fake()->createWithContent('Görev Raporları.json', $json)]
        )->assertForbidden();

        $firstImport = $this->actingAs($admin)->postJson(
            '/valiant-shade/pages/quest-reports/import',
            ['file' => UploadedFile::fake()->createWithContent('Görev Raporları.json', $json)]
        );
        $firstImport->assertOk();
        $firstImport->assertJsonPath('imported', 1);
        $this->assertDatabaseHas('quests', [
            'title' => 'JSON İçe Aktarma Test Görevi',
            'base_xp' => 750,
            'report_reward_xp' => 75,
        ]);

        $secondImport = $this->actingAs($admin)->postJson(
            '/valiant-shade/pages/quest-reports/import',
            ['file' => UploadedFile::fake()->createWithContent('Görev Raporları.json', $json)]
        );
        $secondImport->assertOk();
        $secondImport->assertJsonPath('imported', 0);
        $secondImport->assertJsonPath('skipped', 1);

        $report = collect($this->service->getAllReportsData())
            ->firstWhere('title', 'JSON İçe Aktarma Test Görevi');
        $this->assertNotNull($report);

        $this->actingAs($admin)->putJson(
            "/valiant-shade/pages/quest-reports/{$report['id']}",
            [
                'title' => 'Güncellenmiş Test Görevi',
                'date' => '2026-10-12',
                'level' => 1,
                'gm' => 'Import Test GM',
                'reporter' => 'Import Test Reporter',
            ]
        )->assertOk()->assertJsonPath('report.title', 'Güncellenmiş Test Görevi');
        $this->assertDatabaseHas('quests', ['title' => 'Güncellenmiş Test Görevi']);

        $delete = $this->actingAs($admin)->deleteJson(
            "/valiant-shade/pages/quest-reports/{$report['id']}"
        );
        $delete->assertOk();
        $delete->assertJsonPath('success', true);
        $this->assertSoftDeleted('quests', ['title' => 'Güncellenmiş Test Görevi']);
    }

    public function test_report_import_controls_and_invalid_json_error_are_localized(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        foreach ([
            'en' => ['Import Data', 'The selected file is not valid JSON.'],
            'tr' => ['Veri Ekle', 'Seçilen dosya geçerli bir JSON değil.'],
        ] as $locale => [$label, $message]) {
            $this->actingAs($admin)->withSession(['locale' => $locale])
                ->get('/valiant-shade/pages/quest-reports')->assertOk()->assertSee($label);
            $this->actingAs($admin)->withSession(['locale' => $locale])
                ->postJson('/valiant-shade/pages/quest-reports/import', [
                    'file' => UploadedFile::fake()->createWithContent('reports.json', '{'),
                ])->assertUnprocessable()->assertJsonPath('message', $message);
        }
    }

    public function test_import_requires_full_dates_and_keeps_repeated_rows(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $rows = [
            ['Görev adı' => 'Yıl İçe Aktarma Testi', 'Tarih' => '08/08/2021'],
            ['Görev adı' => 'Yıl İçe Aktarma Testi', 'Tarih' => '2021-08-08'],
            ['Görev adı' => 'Yıl İçe Aktarma Testi', 'Tarih' => '08/08/2022'],
            ['Görev adı' => 'Yıl İçe Aktarma Testi', 'Tarih' => '8/8'],
            ['Görev adı' => 'Yıl İçe Aktarma Testi', 'Tarih' => '29/02/2021'],
        ];
        $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => UploadedFile::fake()->createWithContent('reports.json', $json),
        ])->assertOk()->assertJsonPath('imported', 3)->assertJsonPath('skipped', 2);

        $dates = Quest::where('title', 'Yıl İçe Aktarma Testi')
            ->orderBy('quest_date')
            ->get()
            ->map(fn (Quest $quest) => $quest->quest_date->toDateString())
            ->all();
        $this->assertSame(['2021-08-08', '2021-08-08', '2022-08-08'], $dates);
    }

    public function test_import_and_edit_distinguish_same_day_reports_by_player_roster(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $first = [
            'Görev adı' => 'Castello Operasyonu', 'Tarih' => '2021-10-31',
            'GM' => 'Bart', 'Raporlayan' => 'Aelinor', 'Diğer Oyuncular' => ['Bismuth', 'Boris'],
        ];
        $second = [
            ...$first, 'GM' => 'Shalvaz', 'Raporlayan' => 'Dobby', 'Diğer Oyuncular' => ['Elindel', 'Hall'],
        ];
        $reordered = [
            ...$first, 'GM' => 'Another GM', 'Raporlayan' => ' boris ', 'Diğer Oyuncular' => ['BISMUTH', 'Aelinor'],
        ];
        $upload = fn (array $rows) => UploadedFile::fake()->createWithContent('reports.json', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => $upload([$first]),
        ])->assertOk()->assertJsonPath('imported', 1);
        $reports = $this->service->getAllReportsData();
        $index = collect($reports)->search(fn ($report) => $report['title'] === 'Castello Operasyonu');
        $originalQuestId = $reports[$index]['quest_id'];
        unset($reports[$index]['quest_id']);
        file_put_contents(storage_path('app/quest_reports_testing.json'), json_encode($reports, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => $upload([$first, $second, $reordered]),
        ])->assertOk()->assertJsonPath('imported', 2)->assertJsonPath('skipped', 1);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => $upload([$first, $second, $reordered]),
        ])->assertOk()->assertJsonPath('imported', 0)->assertJsonPath('skipped', 3);

        $reports = collect($this->service->getAllReportsData())->where('title', 'Castello Operasyonu');
        $this->assertCount(3, $reports);
        $this->assertSame($originalQuestId, $reports->firstWhere('reporter', 'Aelinor')['quest_id']);
        $secondReport = $reports->firstWhere('reporter', 'Dobby');
        $this->assertNotSame($originalQuestId, $secondReport['quest_id']);

        $payload = ['title' => 'Castello Güncellendi', 'date' => '2021-10-31', 'level' => 1,
            'gm' => 'Shalvaz', 'reporter' => 'Dobby', 'other_players' => ['Elindel', 'Hall']];
        $this->actingAs($admin)->putJson("/valiant-shade/pages/quest-reports/{$secondReport['id']}", $payload)->assertOk();
        $this->assertDatabaseHas('quests', ['id' => $originalQuestId, 'title' => 'Castello Operasyonu']);
        $this->assertDatabaseHas('quests', ['id' => $secondReport['quest_id'], 'title' => 'Castello Güncellendi']);
        $this->actingAs($admin)->deleteJson("/valiant-shade/pages/quest-reports/{$secondReport['id']}")->assertOk();
        $this->assertDatabaseHas('quests', ['id' => $originalQuestId, 'deleted_at' => null]);
        $this->assertSoftDeleted('quests', ['id' => $secondReport['quest_id']]);
    }

    public function test_import_preserves_identical_row_counts_without_readding_them(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $row = [
            'Görev adı' => 'Tekrarlanan Görev', 'Tarih' => '2021-10-31',
            'Raporlayan' => 'Aelinor', 'Diğer Oyuncular' => ['Bismuth'],
        ];
        $upload = fn (array $rows) => UploadedFile::fake()->createWithContent('reports.json', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => $upload([$row, $row]),
        ])->assertOk()->assertJsonPath('imported', 2)->assertJsonPath('skipped', 0);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => $upload([$row, $row]),
        ])->assertOk()->assertJsonPath('imported', 0)->assertJsonPath('skipped', 2);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => $upload([$row, $row, $row]),
        ])->assertOk()->assertJsonPath('imported', 1)->assertJsonPath('skipped', 2);
        $this->assertCount(3, collect($this->service->getAllReportsData())->where('title', 'Tekrarlanan Görev'));
    }

    public function test_admin_can_clear_all_reports_and_reset_player_xp(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $characterId = DB::table('characters')->insertGetId([
            'user_id' => $admin->id,
            'name' => 'Reset Character',
            'current_level' => 3,
            'total_xp' => 4000,
            'quest_xp' => 4000,
        ]);
        DB::table('player_xps')->insert([
            'character_name' => 'Reset Character',
            'level' => 3,
            'mission_xp' => 4000,
            'total_xp' => 4000,
        ]);
        DB::table('character_xp')->insert(['character_id' => $characterId, 'current_level' => 3, 'total_xp' => 4000, 'quest_xp' => 4000]);
        DB::table('user_xp')->insert(['user_id' => $admin->id, 'total_xp' => 4000, 'wiki_xp' => 4000]);
        DB::table('character_profiles')->insert(['character_name' => 'Reset Character', 'level' => 3]);
        DB::table('xp_ledger')->insert(['character_id' => $characterId, 'amount' => 4000, 'category' => 'QUEST']);
        $questId = DB::table('quests')->insertGetId(['title' => 'Reset Quest']);
        DB::table('quest_participants')->insert(['quest_id' => $questId, 'character_id' => $characterId]);

        $page = $this->actingAs($admin)->get('/valiant-shade/pages/quest-reports');
        $page->assertSee('btnClearQuestReports')->assertDontSee('btnResetToMasterDefaults');
        $this->assertMatchesRegularExpression('/id="btnClearQuestReports">\s*Tüm Raporları Sil\s*<\/button>/u', $page->getContent());
        $this->actingAs($admin)->deleteJson('/valiant-shade/pages/quest-reports')->assertOk()->assertJsonPath('success', true);

        $this->assertSame([], $this->service->getAllReportsData());
        $this->assertSame(0, DB::table('quests')->count());
        $this->assertSame(0, DB::table('quest_participants')->count());
        $this->assertSame(0, DB::table('xp_ledger')->count());
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Reset Character', 'level' => 1, 'mission_xp' => 0, 'total_xp' => 0]);
        $this->assertDatabaseHas('characters', ['id' => $characterId, 'current_level' => 1, 'quest_xp' => 0, 'total_xp' => 0]);
        $this->assertDatabaseHas('character_xp', ['character_id' => $characterId, 'current_level' => 1, 'quest_xp' => 0, 'total_xp' => 0]);
        $this->assertDatabaseHas('user_xp', ['user_id' => $admin->id, 'wiki_xp' => 0, 'total_xp' => 0]);
        $this->assertDatabaseHas('character_profiles', ['character_name' => 'Reset Character', 'level' => 1]);
        $this->actingAs($admin)->getJson('/valiant-shade/pages/quest-reports')->assertOk()->assertJsonPath('stats.total_quests', 0);
    }

    public function test_non_admin_cannot_clear_reports_and_empty_xp_tracker_is_not_reseeded(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/quest-reports')->assertDontSee('id="btnClearQuestReports"', false);
        $this->actingAs($this->user)->deleteJson('/valiant-shade/pages/quest-reports')->assertForbidden();
        $this->assertNotEmpty($this->service->getAllReportsData());

        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($admin)->deleteJson('/valiant-shade/pages/quest-reports')->assertOk();
        $this->actingAs($admin)->get('/valiant-shade/pages/xp-tracker')->assertOk();
        $this->assertSame(0, DB::table('player_xps')->count());
    }

    public function test_clear_succeeds_when_legacy_character_xp_table_is_missing(): void
    {
        Schema::drop('character_xp');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        DB::table('player_xps')->insert(['character_name' => 'Reset Character', 'level' => 3, 'total_xp' => 4000]);
        DB::table('quests')->insert(['title' => 'Reset Quest']);

        $this->actingAs($admin)->deleteJson('/valiant-shade/pages/quest-reports')->assertOk()->assertJsonPath('success', true);
        $this->assertSame([], $this->service->getAllReportsData());
        $this->assertDatabaseMissing('quests', ['title' => 'Reset Quest']);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Reset Character', 'level' => 1, 'total_xp' => 0]);
    }

    public function test_clear_rolls_back_database_if_archive_write_fails(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'quest-report-');
        $this->assertNotFalse($path);
        $original = '[{"id":1,"title":"Existing"}]';
        file_put_contents($path, $original);
        $service = new class($path) extends QuestReportService
        {
            public function __construct(private string $path) {}

            protected function getStoragePath(): string
            {
                return $this->path;
            }

            protected function saveReportsData(array $reports): void
            {
                throw new \RuntimeException('Archive write failed');
            }
        };
        DB::table('quests')->insert(['title' => 'Existing']);
        DB::table('player_xps')->insert(['character_name' => 'Existing', 'total_xp' => 4000, 'level' => 3]);

        try {
            try {
                $service->clearReportsAndPlayerXp();
                $this->fail('The failed archive write must be reported.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Archive write failed', $e->getMessage());
            }
            $this->assertDatabaseHas('quests', ['title' => 'Existing']);
            $this->assertDatabaseHas('player_xps', ['character_name' => 'Existing', 'total_xp' => 4000, 'level' => 3]);
            $this->assertSame($original, file_get_contents($path));
        } finally {
            unlink($path);
            unlink($path.'.lock');
        }
    }
}
