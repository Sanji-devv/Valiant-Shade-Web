<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Quest;
use App\ValiantShade\Services\QuestReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Support\IsolatedQuestReportService;

class QuestReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected IsolatedQuestReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'ADMIN']);
        $path = tempnam(sys_get_temp_dir(), 'quest-reports-test-');
        $this->assertNotFalse($path);
        $this->service = new IsolatedQuestReportService($path);
        $this->app->instance(QuestReportService::class, $this->service);
        $this->service->resetToExcelDefaults();
        $this->service->resetGuildPoolDefaults();
    }

    protected function tearDown(): void
    {
        $this->service->cleanup();
        parent::tearDown();
    }

    public function test_quest_reports_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/quest-reports');
        $response->assertStatus(200);
        $response->assertSee('Görev Raporları');
        $response->assertSee('Kasadaki Para');
        $response->assertSee('Harken Ayaklanması Pt-1');
        $response->assertSee('Wellowburn Hanı Soruşturması Pt-1');
        $response->assertSee('175.63');

        // Guild Pool & Ranks Table Assertions
        $response->assertSee('Lonca XP & Rütbeler Havuzu');
        $response->assertSee('Wiki XP');
        $response->assertSee('14,253.00');
        $response->assertSee('98,295.22');
        $response->assertSee('3,459,587.50');
        $response->assertSee('3,439,017.50');
        $response->assertSee('Bailiff');
        $response->assertSee('Uzman');
        $response->assertSee('Kıdemli');
        $response->assertSee('Clerk');

        // New Report & Calculator Button & Modal assertions
        $response->assertSee('Görev Raporu Ekle');
        $response->assertSee('btnTopOpenAddReportModal');
        $response->assertSee('modalAddQuestReport');
        $response->assertSee('liveAddAvgXpBadge');
        $response->assertSee('liveAddPlayersDistributionList');
        $response->assertSee('addReportDepoDisplay');
        $response->assertSee('addReportLevelBadge');
    }

    public function test_turkish_alias_route_works(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/gorev-raporlari');
        $response->assertStatus(200);
        $response->assertSee('Kasadaki Para');
    }

    public function test_quest_report_formulas_and_calculations(): void
    {
        // 1. Kasadaki Para: Level 1, Side (multiplier 1), Depo OK (depo_cut=0), Rapor=10, Ek=0, Oyuncu=4
        $r1 = $this->service->getReportById(1);
        $this->assertNotNull($r1);
        $this->assertEquals(750, $r1['total_xp']);
        $this->assertEquals(37.5, $r1['guild_cut']);
        $this->assertEquals(0, $r1['depo_cut']);
        $this->assertEquals(10, $r1['rapor_cut']);
        $this->assertEquals(175.63, $r1['avg_reward']);
        $this->assertEquals(4, $r1['player_count']);

        // 2. Harken Ayaklanması Pt-1: Level 1, Epic (multiplier 3) -> 2250 XP, Lonca = 112.5, Oyuncu = 6 -> Ortalama = 356.25
        $r4 = $this->service->getReportById(4);
        $this->assertNotNull($r4);
        $this->assertEquals(2250, $r4['total_xp']);
        $this->assertEquals(112.5, $r4['guild_cut']);
        $this->assertEquals(0, $r4['depo_cut']);
        $this->assertEquals(356.25, $r4['avg_reward']);
        $this->assertEquals(6, $r4['player_count']);

        // 3. Wellowburn Hanı: Depo = 150 (20% penalty), Rapor = 50, Oyuncu = 4 -> Ortalama = 128.13
        $r12 = $this->service->getReportById(12);
        $this->assertNotNull($r12);
        $this->assertEquals(750, $r12['total_xp']);
        $this->assertEquals(37.5, $r12['guild_cut']);
        $this->assertEquals(150, $r12['depo_cut']);
        $this->assertEquals(50, $r12['rapor_cut']);
        $this->assertEquals(128.13, $r12['avg_reward']);

        // 4. Liman Çetelerini Soruşturma Sq-1: Level 2 -> 1000 XP, Lonca = 50, Rapor = 50, Ek = 150, Oyuncu = 5 -> Ortalama = 210.00
        $r25 = $this->service->getReportById(25);
        $this->assertNotNull($r25);
        $this->assertEquals(1000, $r25['total_xp']);
        $this->assertEquals(50, $r25['guild_cut']);
        $this->assertEquals(150, $r25['extra_reward']);
        $this->assertEquals(210.00, $r25['avg_reward']);
        $this->assertEquals(5, $r25['player_count']);
    }

    public function test_create_quest_report_api(): void
    {
        $payload = [
            'title' => 'Karanlık Orman Baskını',
            'date' => '2026-09-24',
            'mq' => 'epic',
            'level' => 2,
            'depo_status' => '1',
            'rapor_cut' => 50,
            'extra_reward' => 100,
            'gm' => 'Bart',
            'reporter' => 'Tolrun',
            'other_players' => ['Veigar', 'Shantar', 'Allerion'],
        ];

        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', $payload);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('report.total_xp', 3000);
        $response->assertJsonPath('report.guild_cut', 150);
        $response->assertJsonPath('report.avg_reward', 725);
        $response->assertJsonPath('report.player_count', 4);
    }

    public function test_reset_to_defaults_api(): void
    {
        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports/reset-defaults');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(32, $response->json('reports'));
    }

    public function test_missing_archive_is_initialized_without_resetting_quest_data(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'quest-report-');
        $this->assertNotFalse($path);
        unlink($path);

        $service = new class($path) extends QuestReportService
        {
            public function __construct(private string $path) {}

            protected function getStoragePath(): string
            {
                return $this->path;
            }

            public function resetToDefaults(): array
            {
                throw new \LogicException('A page load must not reset quest data.');
            }
        };

        try {
            $service->ensureInitialized();
            $this->assertFileExists($path);
            $this->assertCount(32, json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR));
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_empty_archive_remains_empty(): void
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
        };

        try {
            $this->assertSame([], $service->getAllReportsData());
        } finally {
            unlink($path);
        }
    }

    public function test_report_dates_require_a_year_and_duplicate_names_are_checked_per_date(): void
    {
        $payload = [
            'title' => 'Yıl Kontrol Görevi',
            'date' => '2021-08-08',
            'level' => 1,
            'gm' => 'Bart',
            'reporter' => 'Tolrun',
        ];

        $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', $payload)->assertOk();
        $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', [
            ...$payload, 'date' => '08/08/2021',
        ])->assertUnprocessable()->assertJsonValidationErrors('title');
        $second = $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', [
            ...$payload, 'date' => '2022-08-08',
        ])->assertOk()->json('report.id');
        $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', [
            ...$payload, 'date' => '8/8',
        ])->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', [
            ...$payload, 'date' => '2021-02-29',
        ])->assertUnprocessable()->assertJsonValidationErrors('date');

        $this->actingAs($this->user)->deleteJson("/valiant-shade/pages/quest-reports/{$second}")->assertOk();
        $remaining = Quest::where('title', $payload['title'])->get();
        $this->assertCount(1, $remaining);
        $this->assertSame('2021-08-08', $remaining->first()->quest_date->toDateString());
    }

    public function test_manual_report_duplicate_check_uses_the_entire_player_roster(): void
    {
        $payload = [
            'title' => 'Ekip Görevi',
            'date' => '2021-10-31',
            'level' => 1,
            'gm' => 'Bart',
            'reporter' => 'Aelinor',
            'other_players' => ['Bismuth', 'Boris'],
        ];
        $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', $payload)->assertOk();
        $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', [
            ...$payload, 'reporter' => 'Boris', 'other_players' => ['Aelinor', 'Bismuth'],
        ])->assertUnprocessable()->assertJsonValidationErrors('title');
        $secondId = $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports', [
            ...$payload, 'reporter' => 'Dobby', 'other_players' => ['Elindel'],
        ])->assertOk()->json('report.id');
        $this->actingAs($this->user)->putJson("/valiant-shade/pages/quest-reports/{$secondId}", [
            ...$payload, 'reporter' => 'Boris', 'other_players' => ['Bismuth', 'Aelinor'],
        ])->assertUnprocessable()->assertJsonValidationErrors('title');
    }

    public function test_guild_pool_and_ranks_exact_master_sheet_formulas(): void
    {
        $poolData = $this->service->getGuildPoolData();

        // 1. Wiki XP: =BW2-BX2 -> 53800 - 39547 = 14253
        $this->assertEquals(53800.0, $poolData['wiki_xp']['total']);
        $this->assertEquals(39547.0, $poolData['wiki_xp']['distributed']);
        $this->assertEquals(14253.0, $poolData['wiki_xp']['remaining']);

        // 2. Lonca XP & Level
        $this->assertEquals(145000.0, $poolData['guild_xp']['base']);
        $this->assertEquals(157150.0, $poolData['guild_xp']['total']);
        $this->assertEquals(210000.0, $poolData['guild_xp']['next_level_threshold']);
        $this->assertEquals(12, $poolData['guild_xp']['level']);

        // 3. Uzman Havuz & Pay: Havuz = 2064199.61, Uzman = 21 -> Pay = 98295.21975
        $this->assertEqualsWithDelta(2064199.61, $poolData['pool_total'], 0.01);
        $this->assertEqualsWithDelta(98295.21975, $poolData['pool_share_uzman'], 0.001);

        // 4. Ranks Table: Uzman Kapasite XP = BW3 * 0.6 = 94290.00
        $ranks = collect($poolData['ranks'])->keyBy('rank');
        $uzman = $ranks->get('Uzman');
        $this->assertNotNull($uzman);
        $this->assertEquals(21, $uzman['current_count']);
        $this->assertEquals(94290.00, $uzman['capacity_xp']);
        $this->assertEquals(98295.22, $uzman['net_xp']);
        $this->assertEquals(2064199.61, $uzman['spent_xp']);

        // 5. Kıdemli: Net XP = CA11 / BW11 = 401350.01 / 20 = 20067.50
        $kidemli = $ranks->get('Kıdemli');
        $this->assertNotNull($kidemli);
        $this->assertEquals(20067.50, $kidemli['net_xp']);

        // 6. Üye: Net XP = CA12 / BW12 = 30047.37 / 20 = 1502.37
        $uye = $ranks->get('Üye');
        $this->assertNotNull($uye);
        $this->assertEquals(1502.37, $uye['net_xp']);

        // 7. Clerk: Net XP = CA13 / BW13 = 198000.00 / 60 = 3300.00
        $clerk = $ranks->get('Clerk');
        $this->assertNotNull($clerk);
        $this->assertEquals(3300.00, $clerk['net_xp']);

        // 8. Rütbeler Toplamı = 3,439,017.50, Ek = 20,570.00 -> Grand Total = 3,459,587.50
        $this->assertEquals(3439017.50, $poolData['ranks_total_spent']);
        $this->assertEquals(20570.0, $poolData['extra_fund']);
        $this->assertEquals(3459587.50, $poolData['grand_total_xp']);
    }

    public function test_guild_pool_api_endpoints(): void
    {
        // 1. Get Guild Pool
        $resGet = $this->actingAs($this->user)->getJson('/valiant-shade/pages/quest-reports-pool');
        $resGet->assertStatus(200);
        $resGet->assertJsonPath('success', true);
        $resGet->assertJsonPath('guildPoolData.wiki_xp.remaining', 14253);

        // 2. Reset Guild Pool
        $resReset = $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports-pool/reset');
        $resReset->assertStatus(200);
        $resReset->assertJsonPath('success', true);
        $resReset->assertJsonPath('guildPoolData.grand_total_xp', 3459587.5);
    }

    public function test_guild_pool_zero_values_are_preserved_on_update_and_page(): void
    {
        $this->actingAs($this->user)->postJson('/valiant-shade/pages/quest-reports-pool/update', [
            'wiki_xp' => ['total' => 0, 'distributed' => 0],
            'guild_xp' => ['total' => 0, 'base' => 0, 'next_level_threshold' => 0, 'remaining' => 0, 'level' => 1],
            'pool_total' => 0,
            'extra_fund' => 0,
        ])->assertOk()->assertJsonPath('guildPoolData.pool_total', 0)
            ->assertJsonPath('guildPoolData.wiki_xp.remaining', 0);

        $html = $this->actingAs($this->user)->get('/valiant-shade/pages/quest-reports')->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        foreach (['kpiWikiTotalVal', 'kpiWikiDistVal', 'kpiGuildRemainingVal', 'kpiPoolTotalVal'] as $id) {
            $this->assertSame('0.00', trim($xpath->query('//*[@id="'.$id.'"]')->item(0)->textContent));
        }
    }
}
