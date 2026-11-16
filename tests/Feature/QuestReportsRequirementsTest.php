<?php

namespace Tests\Feature;

use App\Models\User;
use App\ValiantShade\Services\QuestReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
}
