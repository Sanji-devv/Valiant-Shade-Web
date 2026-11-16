<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\PlayerXp;
use App\Models\Quest;
use App\Models\User;
use App\ValiantShade\Services\PlayerXpImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlayerXpImportTest extends TestCase
{
    use RefreshDatabase;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = tempnam(sys_get_temp_dir(), 'xp-import-');
    }

    protected function tearDown(): void
    {
        unlink($this->path);
        parent::tearDown();
    }

    public function test_import_matches_known_and_unknown_owners_and_keeps_fallen_names_distinct(): void
    {
        $owner = User::factory()->create(['name' => 'Player One']);
        $unknown = User::factory()->create(['name' => 'Unknown 2026 1']);
        Character::create(['user_id' => $owner->id, 'name' => 'Hero', 'character_type' => 'MAIN', 'status' => 'ACTIVE']);
        Character::create(['user_id' => $unknown->id, 'name' => 'Mystery', 'character_type' => 'MAIN', 'status' => 'PENDING']);
        $existing = PlayerXp::create(['user_id' => $unknown->id, 'character_name' => 'Mystery', 'player_name' => 'Unknown 2026 1']);
        $rows = [
            ['İsim' => 'Hero', 'Oyuncu' => 'Player One', 'Rütbe' => 'Üye', 'Level' => 2, 'XP' => 1300.7, 'Görev_2' => 1300.7, 'İsim_2' => 'Hero', 'Oyuncu_2' => 'Other Player', 'Sebep' => 'KIA', 'XP_2' => 260, 'Görev_3' => 520],
            ['İsim' => 'Mystery', 'Oyuncu' => 'Unknown 8', 'Rütbe' => 'Inaktif', 'XP' => 212.5, 'Görev_2' => 212.5, 'İsim_2' => 'Lost', 'Oyuncu_2' => 'Unknown 9', 'Sebep' => 'MIA', 'XP_2' => 100, 'Görev_3' => 200],
            ['İsim' => null, 'İsim_2' => 'Lost', 'Oyuncu_2' => 'Unknown 9', 'Sebep' => 'MIA', 'XP_2' => 100, 'Görev_3' => 200],
        ];
        file_put_contents($this->path, json_encode($rows, JSON_THROW_ON_ERROR));

        $this->artisan('xp:import', ['path' => $this->path])->assertSuccessful();
        $this->artisan('xp:import', ['path' => $this->path])->assertSuccessful();

        $this->assertDatabaseCount('player_xps', 4);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Hero', 'user_id' => $owner->id, 'status' => 'active', 'total_xp' => 1301]);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Hero', 'player_name' => 'Other Player', 'user_id' => null, 'status' => 'kia', 'mission_xp' => 520, 'total_xp' => 260]);
        $this->assertDatabaseHas('player_xps', ['id' => $existing->id, 'user_id' => $unknown->id, 'player_name' => 'Unknown 8', 'status' => 'inactive']);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Lost', 'status' => 'mia', 'user_id' => null]);
        $this->actingAs($owner)->get('/valiant-shade/pages/xp-tracker')->assertOk()->assertSee('Mystery')->assertSee('Lost');
    }

    public function test_import_rolls_back_if_a_report_has_distributed_xp(): void
    {
        $xp = PlayerXp::create(['character_name' => 'Hero', 'player_name' => 'Player', 'total_xp' => 10]);
        $quest = Quest::create(['title' => 'Report']);
        DB::table('quest_report_xp_distributions')->insert([
            'quest_id' => $quest->id,
            'awards' => json_encode([['player_xp_id' => $xp->id]], JSON_THROW_ON_ERROR),
        ]);
        file_put_contents($this->path, json_encode([
            ['İsim' => 'New', 'Oyuncu' => 'Player', 'İsim_2' => null],
            ['İsim' => 'Hero', 'Oyuncu' => 'Player', 'XP' => 100, 'İsim_2' => null],
        ], JSON_THROW_ON_ERROR));

        $this->artisan('xp:import', ['path' => $this->path])->assertFailed();
        $this->assertDatabaseCount('player_xps', 1);
        $this->assertDatabaseHas('player_xps', ['id' => $xp->id, 'total_xp' => 10]);
    }

    public function test_invalid_json_does_not_change_records(): void
    {
        file_put_contents($this->path, '{');

        $this->artisan('xp:import', ['path' => $this->path])->assertFailed();
        $this->assertDatabaseCount('player_xps', 0);
    }

    public function test_import_errors_follow_the_selected_locale(): void
    {
        $path = $this->path.'-missing';
        foreach ([
            'en' => "The XP file could not be read: {$path}",
            'tr' => "XP dosyası okunamadı: {$path}",
        ] as $locale => $message) {
            app()->setLocale($locale);
            try {
                app(PlayerXpImportService::class)->import($path);
                $this->fail('Importing a missing file must fail.');
            } catch (\RuntimeException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
        app()->setLocale('en');
    }
}
