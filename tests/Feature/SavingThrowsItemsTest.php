<?php

namespace Tests\Feature;

use App\Models\CharacterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingThrowsItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_items_enum_loads_and_contains_cloaks_of_resistance(): void
    {
        $saveItems = config_path('enums/pathfinder_save_items.php');
        $this->assertFileExists($saveItems);

        $items = require $saveItems;
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);

        $names = array_column($items, 'name');
        $this->assertContains('Cloak of Resistance +1', $names);
        $this->assertContains('Cloak of Resistance +2', $names);
        $this->assertContains('Cloak of Resistance +3', $names);
        $this->assertContains('Cloak of Resistance +4', $names);
        $this->assertContains('Cloak of Resistance +5', $names);
        $this->assertContains('Stone of Good Luck (Luckstone)', $names);
    }

    public function test_character_sheet_page_renders_saving_throws_items_ui(): void
    {
        $user = User::factory()->create([
            'email' => 'adventurer@valiantshade.com',
            'name' => 'Sir Valiant',
        ]);

        $response = $this->actingAs($user)->get(route('valiant-shade.character-sheet'));

        $response->assertStatus(200);
        $response->assertSee('sectionIntegratedSaveItems');
        $response->assertSee('tableSaveItems');
        $response->assertSee('btnAddSaveItemRow');
        $response->assertSee('saveItemsTbody');
        $response->assertSee('badgeActiveResistance');
        $response->assertSee('badgeActiveSaveBonuses');
        $response->assertSee('Cloak of Resistance');
    }

    public function test_character_sheet_saves_and_persists_save_items(): void
    {
        $user = User::factory()->create([
            'email' => 'hero@valiantshade.com',
            'name' => 'Knight Paladin',
        ]);

        $saveItemsPayload = [
            [
                'name' => 'Cloak of Resistance +2',
                'target' => 'all',
                'type' => 'resistance',
                'bonus' => 2,
                'weight' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Stone of Good Luck (Luckstone)',
                'target' => 'all',
                'type' => 'luck',
                'bonus' => 1,
                'weight' => 0,
                'is_active' => true,
            ],
        ];

        $postData = [
            'character_name' => 'Arthur Pendragon',
            'className' => 'Paladin',
            'level' => 3,
            'hp' => 28,
            'maxHp' => 28,
            'sheetData' => [
                'player' => 'Sir Arthur',
                'saveMagic_fort' => 3,
                'saveMagic_ref' => 3,
                'saveMagic_will' => 3,
                'saveItems' => $saveItemsPayload,
                'abilityScores' => [
                    'str' => 16,
                    'dex' => 12,
                    'con' => 14,
                    'int' => 10,
                    'wis' => 12,
                    'cha' => 15,
                ],
            ],
        ];

        $saveResponse = $this->actingAs($user)->postJson(route('valiant-shade.character-sheet.save'), $postData);
        $saveResponse->assertStatus(200);
        $saveResponse->assertJson(['success' => true]);

        $profile = CharacterProfile::where('character_name', 'Arthur Pendragon')->first();
        $this->assertNotNull($profile);

        $savedSheetData = is_array($profile->sheet_data) ? $profile->sheet_data : json_decode($profile->sheet_data, true);
        $this->assertArrayHasKey('sheetData', $savedSheetData);
        $this->assertArrayHasKey('saveItems', $savedSheetData['sheetData']);
        $this->assertCount(2, $savedSheetData['sheetData']['saveItems']);
        $this->assertEquals('Cloak of Resistance +2', $savedSheetData['sheetData']['saveItems'][0]['name']);
        $this->assertEquals(2, $savedSheetData['sheetData']['saveItems'][0]['bonus']);
        $this->assertTrue($savedSheetData['sheetData']['saveItems'][0]['is_active']);
    }
}
