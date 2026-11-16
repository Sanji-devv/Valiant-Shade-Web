<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Quest;
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

    public function test_json_import_persists_reports_and_delete_button_route_works(): void
    {
        $page = $this->actingAs($this->user)->get('/valiant-shade/pages/quest-reports');
        $page->assertSee('btnImportQuestReports');
        $page->assertSee('Veri Ekle');
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

    public function test_import_requires_full_dates_and_treats_different_years_as_distinct(): void
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
        ])->assertOk()->assertJsonPath('imported', 2)->assertJsonPath('skipped', 3);

        $dates = Quest::where('title', 'Yıl İçe Aktarma Testi')
            ->orderBy('quest_date')
            ->get()
            ->map(fn (Quest $quest) => $quest->quest_date->toDateString())
            ->all();
        $this->assertSame(['2021-08-08', '2022-08-08'], $dates);
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
        ])->assertOk()->assertJsonPath('imported', 1)->assertJsonPath('skipped', 2);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/quest-reports/import', [
            'file' => $upload([$first, $second, $reordered]),
        ])->assertOk()->assertJsonPath('imported', 0)->assertJsonPath('skipped', 3);

        $reports = collect($this->service->getAllReportsData())->where('title', 'Castello Operasyonu');
        $this->assertCount(2, $reports);
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
        }
    }
}
