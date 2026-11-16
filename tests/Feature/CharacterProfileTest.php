<?php

namespace Tests\Feature;

use App\Models\CharacterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CharacterProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestUser(string $email = 'test@valiantshade.com', string $name = 'TestHero'): User
    {
        return User::factory()->create([
            'email' => $email,
            'name' => $name,
            'password' => bcrypt('password123'),
        ]);
    }

    public function test_user_profile_page_renders_only_own_characters(): void
    {
        $userA = $this->createTestUser('user_a@valiantshade.com', 'User Alpha');
        $userB = $this->createTestUser('user_b@valiantshade.com', 'User Beta');

        CharacterProfile::create([
            'character_id' => 'char_a1',
            'user_id' => $userA->id,
            'character_name' => 'Alpha Knight',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'level' => 1,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 16, 'dex' => 12, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14,
            'hp' => 12, 'max_hp' => 12, 'hit_die' => 'd10',
            'sheet_data' => ['name' => 'Alpha Knight', 'race' => 'Human', 'rank' => 'Main'],
        ]);

        CharacterProfile::create([
            'character_id' => 'char_b1',
            'user_id' => $userB->id,
            'character_name' => 'Beta Rogue',
            'race' => 'Elf',
            'class_name' => 'Rogue',
            'level' => 1,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 10, 'dex' => 18, 'con' => 12, 'int' => 14, 'wis' => 10, 'cha' => 12,
            'hp' => 9, 'max_hp' => 9, 'hit_die' => 'd8',
            'sheet_data' => ['name' => 'Beta Rogue', 'race' => 'Elf', 'rank' => 'Main'],
        ]);

        $response = $this->actingAs($userA)->get('/valiant-shade/pages/profile-user');

        $response->assertStatus(200);
        $response->assertSee('Alpha Knight');
        $response->assertDontSee('Beta Rogue');
    }

    public function test_create_character_requires_all_six_ability_scores(): void
    {
        $user = $this->createTestUser();

        // Missing str
        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Incomplete Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'dex' => 14,
            'con' => 14,
            'int' => 10,
            'wis' => 10,
            'cha' => 10,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['str']);

        // Invalid stat (> 30)
        $response2 = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Super Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'str' => 99,
            'dex' => 14,
            'con' => 14,
            'int' => 10,
            'wis' => 10,
            'cha' => 10,
        ]);

        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['str']);
    }

    public function test_can_create_main_character_with_valid_stats(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Sir Galahad',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'str' => 16,
            'dex' => 12,
            'con' => 14,
            'int' => 10,
            'wis' => 12,
            'cha' => 14,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $char = CharacterProfile::where('character_name', 'Sir Galahad')->first();
        $this->assertNotNull($char);
        $this->assertEquals($user->id, $char->user_id);
        $this->assertEquals('Main', $char->rank);
        $this->assertEquals('Main', $char->character_type);
        $this->assertEquals('Human', $char->race);
        $this->assertEquals(16, $char->str);
        $this->assertEquals(14, $char->con);
        // Paladin HD is d10 (10) + CON mod (+2 for 14) = 12
        $this->assertEquals(12, $char->hp);
        $this->assertEquals(12, $char->max_hp);
    }

    public function test_cannot_create_duplicate_main_character(): void
    {
        $user = $this->createTestUser();

        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'First Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'str' => 14, 'dex' => 14, 'con' => 14, 'int' => 10, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        // Attempt second Main
        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Second Main Hero',
            'rank' => 'Main',
            'race' => 'Dwarf',
            'class_name' => 'Fighter',
            'str' => 16, 'dex' => 12, 'con' => 16, 'int' => 10, 'wis' => 12, 'cha' => 8,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_can_create_clerk_character_as_second_slot(): void
    {
        $user = $this->createTestUser();

        // 1. Create Main
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Warrior Main',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Fighter',
            'str' => 16, 'dex' => 14, 'con' => 14, 'int' => 10, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        // 2. Create Clerk
        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Scribe Clerk',
            'rank' => 'Clerk',
            'race' => 'Elf',
            'class_name' => 'Wizard',
            'str' => 8, 'dex' => 14, 'con' => 12, 'int' => 18, 'wis' => 12, 'cha' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals(2, CharacterProfile::where('user_id', $user->id)->count());
    }

    public function test_cannot_exceed_two_characters(): void
    {
        $user = $this->createTestUser();

        // Main
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Char One',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Fighter',
            'str' => 14, 'dex' => 14, 'con' => 14, 'int' => 10, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        // Clerk
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Char Two',
            'rank' => 'Clerk',
            'race' => 'Elf',
            'class_name' => 'Rogue',
            'str' => 10, 'dex' => 16, 'con' => 12, 'int' => 14, 'wis' => 10, 'cha' => 12,
        ])->assertStatus(200);

        // Third attempt
        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Char Three',
            'rank' => 'Clerk',
            'race' => 'Gnome',
            'class_name' => 'Bard',
            'str' => 10, 'dex' => 14, 'con' => 12, 'int' => 12, 'wis' => 10, 'cha' => 16,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
        $this->assertEquals(2, CharacterProfile::where('user_id', $user->id)->count());
    }

    public function test_race_and_rank_are_immutable_on_character_sheet_save(): void
    {
        $user = $this->createTestUser();

        $char = CharacterProfile::create([
            'character_id' => 'char_immut_test',
            'user_id' => $user->id,
            'character_name' => 'Permanent Hero',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'level' => 1,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 16, 'dex' => 12, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14,
            'hp' => 12, 'max_hp' => 12, 'hit_die' => 'd10',
            'sheet_data' => ['name' => 'Permanent Hero', 'race' => 'Human', 'rank' => 'Main'],
        ]);

        // Attempt to maliciously modify race to "Dragonborn" and rank to "Clerk" via sheet save
        $response = $this->actingAs($user)->postJson('/valiant-shade/character/sheet/save', [
            'id' => $char->character_id,
            'name' => 'Permanent Hero',
            'race' => 'Dragonborn',
            'rank' => 'Clerk',
            'className' => 'Paladin',
            'level' => 2,
            'hp' => 20,
            'maxHp' => 20,
            'sheetData' => [
                'race' => 'Dragonborn',
                'rank' => 'Clerk',
                'level' => 2,
            ],
        ]);

        $response->assertStatus(200);

        $char->refresh();
        // Race and Rank must remain strictly unchanged
        $this->assertEquals('Human', $char->race);
        $this->assertEquals('Main', $char->rank);
        $this->assertEquals('Main', $char->character_type);
        // But level was allowed to update
        $this->assertEquals(2, $char->level);
    }

    public function test_user_cannot_delete_another_users_character(): void
    {
        $userA = $this->createTestUser('owner@valiantshade.com', 'Owner');
        $userB = $this->createTestUser('intruder@valiantshade.com', 'Intruder');

        $char = CharacterProfile::create([
            'character_id' => 'char_protected',
            'user_id' => $userA->id,
            'character_name' => 'Protected Hero',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'level' => 1,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 16, 'dex' => 12, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14,
            'hp' => 12, 'max_hp' => 12, 'hit_die' => 'd10',
            'sheet_data' => ['name' => 'Protected Hero'],
        ]);

        // User B tries to delete User A's character
        $response = $this->actingAs($userB)->deleteJson('/valiant-shade/character/' . $char->character_id);

        $response->assertStatus(404);
        $this->assertDatabaseHas('character_profiles', ['character_id' => 'char_protected']);
    }

    public function test_user_can_delete_own_character(): void
    {
        $user = $this->createTestUser();

        $char = CharacterProfile::create([
            'character_id' => 'char_to_delete',
            'user_id' => $user->id,
            'character_name' => 'Temporary Hero',
            'race' => 'Human',
            'class_name' => 'Fighter',
            'level' => 1,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 14, 'dex' => 14, 'con' => 14, 'int' => 10, 'wis' => 10, 'cha' => 10,
            'hp' => 12, 'max_hp' => 12, 'hit_die' => 'd10',
            'sheet_data' => ['name' => 'Temporary Hero'],
        ]);

        $response = $this->actingAs($user)->deleteJson('/valiant-shade/character/' . $char->character_id);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('character_profiles', ['character_id' => 'char_to_delete']);
    }

    public function test_alchemist_character_creation_adds_only_bomb_weapon(): void
    {
        $user = $this->createTestUser('alchemist_tester@valiantshade.com', 'Alchemist Tester');

        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Edward Elric',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Alchemist',
            'str' => 10,
            'dex' => 16,
            'con' => 12,
            'int' => 18, // +4 Mod
            'wis' => 10,
            'cha' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $profile = CharacterProfile::where('character_name', 'Edward Elric')->first();
        $this->assertNotNull($profile);
        $this->assertEquals('Alchemist', $profile->class_name);

        $weapons = $profile->sheet_data['weapons'] ?? [];
        $this->assertCount(1, $weapons, 'Alchemist must only have exactly 1 weapon: Bomb');

        $bomb = $weapons[0];
        $this->assertEquals('Bomb', $bomb['name']);
        $this->assertEquals('ranged', $bomb['atkType']);
        $this->assertEquals('Fire', $bomb['type']);
        $this->assertEquals('20 ft.', $bomb['range']);
        $this->assertEquals('1d6', $bomb['baseDie']);
        $this->assertEquals('5/day', $bomb['ammo']); // Level 1 + 4 INT = 5/day
    }

    public function test_character_sheet_page_renders_bomb_for_alchemist(): void
    {
        $user = $this->createTestUser('alch_view@valiantshade.com', 'Alchemist Viewer');

        $char = CharacterProfile::create([
            'character_id' => 'char_alch_view',
            'user_id' => $user->id,
            'character_name' => 'Bomb Specialist',
            'race' => 'Gnome',
            'class_name' => 'Alchemist',
            'level' => 3, // Level 3: Bomb damage is 2d6
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 10, 'dex' => 16, 'con' => 14, 'int' => 18, 'wis' => 10, 'cha' => 10,
            'hp' => 20, 'max_hp' => 20, 'hit_die' => 'd8',
            'sheet_data' => [
                'name' => 'Bomb Specialist',
                'className' => 'Alchemist',
                'classKey' => 'alchemist',
                'level' => 3,
                'weapons' => [
                    [
                        'name' => 'Bomb',
                        'atkType' => 'ranged',
                        'type' => 'Fire',
                        'range' => '20 ft.',
                        'baseDie' => '2d6',
                        'enh' => 0,
                        'miscAtk' => 0,
                        'critRange' => '20',
                        'critMult' => 2,
                        'ammo' => '7/day',
                    ]
                ],
            ],
        ]);

        $response = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $char->character_id);

        $response->assertStatus(200);
        $response->assertSee('value="Bomb"', false);
        $response->assertSee('value="2d6"', false);
        $response->assertSee('value="20 ft."', false);
        $response->assertDontSee('value="Longsword +1"', false);
        $response->assertDontSee('value="Heavy Crossbow"', false);
    }

    public function test_alchemist_bomb_damage_scales_at_levels_1_3_5_7(): void
    {
        $user = $this->createTestUser('scale_tester@valiantshade.com', 'Scale Tester');

        $levelsAndExpectedDice = [
            1 => '1d6',
            2 => '1d6',
            3 => '2d6',
            4 => '2d6',
            5 => '3d6',
            6 => '3d6',
            7 => '4d6',
        ];

        foreach ($levelsAndExpectedDice as $level => $expectedDie) {
            $char = CharacterProfile::create([
                'character_id' => 'char_scale_' . $level,
                'user_id' => $user->id,
                'character_name' => 'Alchemist Lvl ' . $level,
                'race' => 'Human',
                'class_name' => 'Alchemist',
                'level' => $level,
                'rank' => 'Main',
                'character_type' => 'Main',
                'str' => 10, 'dex' => 14, 'con' => 14, 'int' => 16, 'wis' => 10, 'cha' => 10,
                'hp' => 10 + ($level * 5), 'max_hp' => 10 + ($level * 5), 'hit_die' => 'd8',
                'sheet_data' => ['name' => 'Alchemist Lvl ' . $level],
            ]);

            $response = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $char->character_id);
            $response->assertStatus(200);
            $response->assertSee('value="Bomb"', false);
            $response->assertSee('value="' . $expectedDie . '"', false);
        }
    }

    public function test_character_feats_come_completely_empty_for_new_characters(): void
    {
        $user = $this->createTestUser('feats_clean_tester@valiantshade.com', 'Feats Clean Tester');

        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Clean Slate Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'str' => 16,
            'dex' => 12,
            'con' => 14,
            'int' => 10,
            'wis' => 12,
            'cha' => 14,
        ]);

        $response->assertStatus(200);
        $profile = CharacterProfile::where('character_name', 'Clean Slate Hero')->first();
        $this->assertNotNull($profile);

        // Verify sheet_data has empty feats
        $this->assertEmpty($profile->sheet_data['feats'] ?? []);
        $this->assertEquals(0, $profile->feats()->count());

        // Verify character sheet view renders no hardcoded dummy feats
        $sheetResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $profile->character_id);
        $sheetResponse->assertStatus(200);
        $sheetResponse->assertDontSee('Power Attack');
        $sheetResponse->assertDontSee('Weapon Focus (Longsword)');
        $sheetResponse->assertDontSee('Toughness');
    }

    public function test_special_abilities_container_has_no_hardcoded_dummy_smite_in_blade(): void
    {
        $user = $this->createTestUser('specials_clean_tester@valiantshade.com', 'Specials Clean Tester');

        $response = $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Alchemical Specialist',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Alchemist',
            'str' => 10,
            'dex' => 14,
            'con' => 12,
            'int' => 18,
            'wis' => 12,
            'cha' => 10,
        ]);

        $response->assertStatus(200);
        $profile = CharacterProfile::where('character_name', 'Alchemical Specialist')->first();
        $this->assertNotNull($profile);

        $sheetResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $profile->character_id);
        $sheetResponse->assertStatus(200);

        // Ensure dummy Smite Evil is NOT hardcoded inside specialsContainer HTML
        $sheetResponse->assertDontSee('<strong class="text-heading small">Smite Evil (1/day)</strong>', false);
        $sheetResponse->assertDontSee('Add CHA bonus to attack and Paladin level to damage vs evil target.', false);
        $sheetResponse->assertSee('<div class="pf-scroll" style="max-height: 520px;" id="specialsContainer"></div>', false);
    }

    public function test_new_characters_get_class_features_in_specials(): void
    {
        $user = $this->createTestUser('class_specials_tester@valiantshade.com', 'Class Specials Tester');

        // 1. Alchemist should get Level 1 Alchemist features
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Edward Elixir',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Alchemist',
            'str' => 10, 'dex' => 14, 'con' => 12, 'int' => 18, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        $alchemist = CharacterProfile::where('character_name', 'Edward Elixir')->first();
        $this->assertNotNull($alchemist);
        $specials = $alchemist->sheet_data['specials'] ?? [];
        $this->assertNotEmpty($specials);
        $titles = array_column($specials, 'title');
        $this->assertContains('Alchemy', $titles);
        $this->assertContains('Bomb 1d6', $titles);
        $this->assertContains('Mutagen', $titles);
        $this->assertContains('Throw anything', $titles);
        $this->assertNotContains('Smite Evil (1/day)', $titles);

        // 2. Clerk character (Rogue) should get Level 1 Rogue features
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Shadow Scout',
            'rank' => 'Clerk',
            'race' => 'Elf',
            'class_name' => 'Rogue',
            'str' => 10, 'dex' => 18, 'con' => 12, 'int' => 14, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        $rogue = CharacterProfile::where('character_name', 'Shadow Scout')->first();
        $this->assertNotNull($rogue);
        $rogueSpecials = $rogue->sheet_data['specials'] ?? [];
        $rogueTitles = array_column($rogueSpecials, 'title');
        $this->assertContains('Sneak attack +1d6', $rogueTitles);
        $this->assertContains('Trapfinding', $rogueTitles);
        $this->assertNotContains('Smite Evil (1/day)', $rogueTitles);
    }

    public function test_experience_points_section_is_removed_from_character_sheet(): void
    {
        $user = $this->createTestUser('xp_removal_tester@valiantshade.com', 'XP Removal Tester');

        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'No XP Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Fighter',
            'str' => 16, 'dex' => 14, 'con' => 14, 'int' => 10, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        $hero = CharacterProfile::where('character_name', 'No XP Hero')->first();
        $this->assertNotNull($hero);

        $sheetResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $hero->character_id);
        $sheetResponse->assertStatus(200);

        // Verify Experience Points (XP) section and inputs are removed from HTML
        $sheetResponse->assertDontSee('Experience Points (XP)');
        $sheetResponse->assertDontSee('id="sheetCurrentXp"', false);
        $sheetResponse->assertDontSee('id="sheetNextLevelXp"', false);
        $sheetResponse->assertDontSee('id="sheetXpProgressBar"', false);
    }

    public function test_casting_stat_and_spells_automation_by_class(): void
    {
        $user = $this->createTestUser('spells_auto_tester@valiantshade.com', 'Spells Auto Tester');

        // 1. Alchemist should have castingStat = int
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Bombastic Alchemist',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Alchemist',
            'str' => 10, 'dex' => 14, 'con' => 12, 'int' => 18, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        $alchemist = CharacterProfile::where('character_name', 'Bombastic Alchemist')->first();
        $this->assertNotNull($alchemist);
        $this->assertEquals('int', $alchemist->sheet_data['castingStat'] ?? null);

        // 2. Paladin should have castingStat = cha
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Holy Defender',
            'rank' => 'Clerk',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'str' => 16, 'dex' => 10, 'con' => 14, 'int' => 10, 'wis' => 10, 'cha' => 14,
        ])->assertStatus(200);

        $paladin = CharacterProfile::where('character_name', 'Holy Defender')->first();
        $this->assertNotNull($paladin);
        $this->assertEquals('cha', $paladin->sheet_data['castingStat'] ?? null);

        // 3. Fighter should have castingStat = none
        $user2 = $this->createTestUser('fighter_no_spells@valiantshade.com', 'Fighter No Spells');
        $this->actingAs($user2)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Iron Warrior',
            'rank' => 'Main',
            'race' => 'Dwarf',
            'class_name' => 'Fighter',
            'str' => 18, 'dex' => 12, 'con' => 16, 'int' => 10, 'wis' => 10, 'cha' => 8,
        ])->assertStatus(200);

        $fighter = CharacterProfile::where('character_name', 'Iron Warrior')->first();
        $this->assertNotNull($fighter);
        $this->assertEquals('none', $fighter->sheet_data['castingStat'] ?? null);

        // Verify character sheet view contains dynamic spellRow elements and castingStat select
        $sheetResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $alchemist->character_id);
        $sheetResponse->assertStatus(200);
        $sheetResponse->assertSee('id="sheetCastingStat"', false);
        $sheetResponse->assertSee('id="lblBaseSpellDc"', false);
        $sheetResponse->assertSee('id="spellRow_0"', false);
        $sheetResponse->assertSee('id="spellRow_1"', false);
    }

    public function test_scaling_special_abilities_keep_only_highest_tier_while_repeatables_are_preserved(): void
    {
        $user = $this->createTestUser('scaling_tester@valiantshade.com', 'Scaling Tester');

        // Create Alchemist
        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'High Lvl Alchemist',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Alchemist',
            'str' => 10, 'dex' => 14, 'con' => 12, 'int' => 18, 'wis' => 10, 'cha' => 10,
        ])->assertStatus(200);

        $alchemist = CharacterProfile::where('character_name', 'High Lvl Alchemist')->first();
        $this->assertNotNull($alchemist);

        // Verify sheet view contains the scaling filter JS logic
        $sheetResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $alchemist->character_id);
        $sheetResponse->assertStatus(200);
        $sheetResponse->assertSee('filterScalingSpecials', false);
        $sheetResponse->assertSee('REPEATABLE_CHOICE_SPECIALS', false);
        $sheetResponse->assertSee('getSpecialScalingKey', false);

        // Simulate sheet save for level 12 Alchemist with filtered specials
        // In this test, we verify that saving specials preserves the highest bomb and all discoveries
        $level12Specials = [
            ['badge' => 'Lvl 1', 'title' => 'Alchemy', 'desc' => 'Alchemist Lvl 1 Class Feature'],
            ['badge' => 'Lvl 1', 'title' => 'Mutagen', 'desc' => 'Alchemist Lvl 1 Class Feature'],
            ['badge' => 'Lvl 1', 'title' => 'Throw anything', 'desc' => 'Alchemist Lvl 1 Class Feature'],
            ['badge' => 'Lvl 2', 'title' => 'Discovery', 'desc' => 'Alchemist Lvl 2 Class Feature'],
            ['badge' => 'Lvl 2', 'title' => 'Poison use', 'desc' => 'Alchemist Lvl 2 Class Feature'],
            ['badge' => 'Lvl 3', 'title' => 'Swift alchemy', 'desc' => 'Alchemist Lvl 3 Class Feature'],
            ['badge' => 'Lvl 4', 'title' => 'Discovery', 'desc' => 'Alchemist Lvl 4 Class Feature'],
            ['badge' => 'Lvl 6', 'title' => 'Discovery', 'desc' => 'Alchemist Lvl 6 Class Feature'],
            ['badge' => 'Lvl 6', 'title' => 'Poison resistance +6', 'desc' => 'Alchemist Lvl 6 Class Feature'],
            ['badge' => 'Lvl 6', 'title' => 'Swift poisoning', 'desc' => 'Alchemist Lvl 6 Class Feature'],
            ['badge' => 'Lvl 8', 'title' => 'Discovery', 'desc' => 'Alchemist Lvl 8 Class Feature'],
            ['badge' => 'Lvl 8', 'title' => 'Poison immunity', 'desc' => 'Alchemist Lvl 8 Class Feature'],
            ['badge' => 'Lvl 10', 'title' => 'Discovery', 'desc' => 'Alchemist Lvl 10 Class Feature'],
            ['badge' => 'Lvl 11', 'title' => 'Bomb 6d6', 'desc' => 'Alchemist Lvl 11 Class Feature'],
            ['badge' => 'Lvl 12', 'title' => 'Discovery', 'desc' => 'Alchemist Lvl 12 Class Feature'],
        ];

        $saveResponse = $this->actingAs($user)->postJson('/valiant-shade/character/sheet/save', [
            'id' => $alchemist->character_id,
            'name' => 'High Lvl Alchemist',
            'level' => 12,
            'hp' => 68,
            'maxHp' => 68,
            'sheetData' => [
                'level' => 12,
                'specials' => $level12Specials,
            ],
        ]);

        $saveResponse->assertStatus(200);
        $alchemist->refresh();

        $savedSpecials = $alchemist->sheet_data['sheetData']['specials'] ?? $alchemist->sheet_data['specials'] ?? [];
        $bombCount = 0;
        $discoveryCount = 0;
        $bombTitles = [];

        foreach ($savedSpecials as $s) {
            if (stripos($s['title'], 'bomb') !== false) {
                $bombCount++;
                $bombTitles[] = $s['title'];
            }
            if (stripos($s['title'], 'discovery') !== false) {
                $discoveryCount++;
            }
        }

        // Only highest Bomb (Bomb 6d6) exists, no duplicates
        $this->assertEquals(1, $bombCount);
        $this->assertEquals(['Bomb 6d6'], $bombTitles);

        // All 6 discoveries across levels 2, 4, 6, 8, 10, 12 exist
        $this->assertEquals(6, $discoveryCount);
    }

    public function test_saving_throws_inputs_are_equal_size_and_notes_are_removed(): void
    {
        $user = $this->createTestUser('save_ui_tester@valiantshade.com', 'Save UI Tester');

        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Save UI Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'str' => 16, 'dex' => 10, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14,
        ])->assertStatus(200);

        $hero = CharacterProfile::where('character_name', 'Save UI Hero')->first();
        $this->assertNotNull($hero);

        $sheetResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $hero->character_id);
        $sheetResponse->assertStatus(200);

        // Verify Modifiers & Notes column is removed
        $sheetResponse->assertDontSee('Modifiers & Notes');
        $sheetResponse->assertDontSee('Poison, disease, bodily resilience');
        $sheetResponse->assertDontSee('Traps, area bursts, agile avoidance');
        $sheetResponse->assertDontSee('Mind-affecting, spell resistance, willpower');

        // Verify bottom conditional save modifiers input is removed
        $sheetResponse->assertDontSee('id="sheetSaveConditional"', false);
        $sheetResponse->assertDontSee('placeholder="Conditional Save Modifiers..."', false);

        // Verify Base, Ability, Magic, Misc inputs are present and use compact uniform styling
        $sheetResponse->assertSee('id="saveBase_fort"', false);
        $sheetResponse->assertSee('id="saveAbility_fort"', false);
        $sheetResponse->assertSee('id="saveMagic_fort"', false);
        $sheetResponse->assertSee('id="saveMisc_fort"', false);

        // Verify old wide inline style on base is removed
        $sheetResponse->assertDontSee('style="min-width: 90px; height: 38px;"', false);

        // Verify Total button and inputs use pf-save-box for identical sizing
        $sheetResponse->assertSee('pf-save-box', false);
        $sheetResponse->assertDontSee('id="saveAbility_fort" class="form-control form-control-sm pf-box-input pf-save-box font-monospace text-center" value="0" readonly', false);
    }

    public function test_character_sheet_loads_armors_data_and_armor_search_ui(): void
    {
        $user = $this->createTestUser('armoruser@valiantshade.com', 'Armor Hero');

        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Armor Test Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Fighter',
            'str' => 16, 'dex' => 16, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 10,
        ])->assertStatus(200);

        $hero = CharacterProfile::where('character_name', 'Armor Test Hero')->first();
        $this->assertNotNull($hero);

        $response = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $hero->character_id);
        $response->assertStatus(200);

        // Verify AC items table and breakdown UI elements
        $response->assertSee('id="tableAcItems"', false);
        $response->assertSee('id="acItemsTbody"', false);
        $response->assertSee('id="btnAddAcItemRow"', false);
        $response->assertSee('id="sheetAcArmor"', false);
        $response->assertSee('id="sheetAcShield"', false);
        $response->assertSee('id="sheetAcDeflect"', false);
        $response->assertSee('id="sheetAcNatural"', false);
        $response->assertSee('id="sheetAcMisc"', false);
        $response->assertSee('id="badgeDexCapInfo"', false);
        $response->assertSee('id="dexCapNotice"', false);

        // Verify Integrated AC section and Active Badges
        $response->assertSee('id="sectionIntegratedAcItems"', false);
        $response->assertSee('id="badgeActiveArmor"', false);
        $response->assertSee('id="lblActiveArmorName"', false);
        $response->assertSee('id="badgeActiveShield"', false);
        $response->assertSee('id="lblActiveShieldName"', false);

        // Verify armors data payload is rendered with Pathfinder armor items
        $response->assertSee('Buckler');
        $response->assertSee('Mithral Shirt');
        $response->assertSee('Ring of Protection +1');
    }

    public function test_character_sheet_saves_and_persists_ac_items(): void
    {
        $user = $this->createTestUser('armorsave@valiantshade.com', 'Armor Saver');

        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Equipped Armor Hero',
            'rank' => 'Main',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'str' => 16, 'dex' => 16, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14,
        ])->assertStatus(200);

        $hero = CharacterProfile::where('character_name', 'Equipped Armor Hero')->first();

        $saveData = [
            'id' => $hero->character_id,
            'name' => 'Equipped Armor Hero',
            'class' => 'Paladin',
            'classKey' => 'paladin',
            'level' => 1,
            'race' => 'Human',
            'sheetData' => [
                'currentHp' => 12,
                'maxHp' => 12,
                'abilityScores' => ['str' => 16, 'dex' => 16, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14],
                'acNatural' => 1,
                'acDeflect' => 2,
                'acMisc' => 1,
                'acItems' => [
                    [
                        'name' => 'Mithral Shirt',
                        'type' => 'armor',
                        'bonus' => 4,
                        'pen' => 0,
                        'weight' => 10,
                        'max_dex' => 6,
                        'category' => 'Light Armors',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Full Plate',
                        'type' => 'armor',
                        'bonus' => 9,
                        'pen' => -6,
                        'weight' => 50,
                        'max_dex' => 1,
                        'category' => 'Heavy Armors',
                        'is_active' => false,
                    ],
                    [
                        'name' => 'Buckler',
                        'type' => 'shield',
                        'bonus' => 1,
                        'pen' => -1,
                        'weight' => 5,
                        'max_dex' => null,
                        'category' => 'Shields',
                        'is_active' => true,
                    ]
                ]
            ]
        ];

        $saveResponse = $this->actingAs($user)->postJson('/valiant-shade/character/sheet/save', $saveData);
        $saveResponse->assertStatus(200);
        $saveResponse->assertJson(['success' => true]);

        $hero->refresh();
        $sheetData = is_string($hero->sheet_data) ? json_decode($hero->sheet_data, true) : $hero->sheet_data;
        $innerData = $sheetData['sheetData'] ?? $sheetData;

        $this->assertEquals(1, $innerData['acNatural']);
        $this->assertEquals(2, $innerData['acDeflect']);
        $this->assertEquals(1, $innerData['acMisc']);
        $this->assertCount(3, $innerData['acItems']);
        $this->assertEquals('Mithral Shirt', $innerData['acItems'][0]['name']);
        $this->assertEquals(4, $innerData['acItems'][0]['bonus']);
        $this->assertTrue($innerData['acItems'][0]['is_active']);
        $this->assertEquals('Full Plate', $innerData['acItems'][1]['name']);
        $this->assertEquals(9, $innerData['acItems'][1]['bonus']);
        $this->assertFalse($innerData['acItems'][1]['is_active']);
        $this->assertEquals('Buckler', $innerData['acItems'][2]['name']);
        $this->assertEquals(1, $innerData['acItems'][2]['bonus']);
        $this->assertTrue($innerData['acItems'][2]['is_active']);
    }

    public function test_character_sheet_supports_multiple_armors_and_shields_with_active_selection(): void
    {
        $user = $this->createTestUser('multiarmor@valiantshade.com', 'Multi Armor Hero');

        $this->actingAs($user)->postJson('/valiant-shade/character/create', [
            'character_name' => 'Armory Master',
            'rank' => 'Main',
            'race' => 'Dwarf',
            'class_name' => 'Fighter',
            'str' => 18, 'dex' => 14, 'con' => 16, 'int' => 10, 'wis' => 12, 'cha' => 8,
        ])->assertStatus(200);

        $hero = CharacterProfile::where('character_name', 'Armory Master')->first();
        $this->assertNotNull($hero);

        // Save multiple armors and shields with active status flags
        $saveData = [
            'id' => $hero->character_id,
            'name' => 'Armory Master',
            'class' => 'Fighter',
            'sheetData' => [
                'acArmor' => 9, // active Full Plate
                'acShield' => 2, // active Heavy Steel Shield
                'acItems' => [
                    [
                        'name' => 'Full Plate',
                        'type' => 'armor',
                        'bonus' => 9,
                        'pen' => -6,
                        'weight' => 50,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Breastplate',
                        'type' => 'armor',
                        'bonus' => 6,
                        'pen' => -4,
                        'weight' => 30,
                        'is_active' => false,
                    ],
                    [
                        'name' => 'Heavy Steel Shield',
                        'type' => 'shield',
                        'bonus' => 2,
                        'pen' => -2,
                        'weight' => 15,
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Light Wooden Shield',
                        'type' => 'shield',
                        'bonus' => 1,
                        'pen' => -1,
                        'weight' => 5,
                        'is_active' => false,
                    ],
                ]
            ]
        ];

        $this->actingAs($user)->postJson('/valiant-shade/character/sheet/save', $saveData)
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        // Load sheet and verify it renders
        $sheetResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $hero->character_id);
        $sheetResponse->assertStatus(200);
        $sheetResponse->assertSee('Full Plate');
        $sheetResponse->assertSee('Breastplate');
        $sheetResponse->assertSee('Heavy Steel Shield');
        $sheetResponse->assertSee('Light Wooden Shield');
        $sheetResponse->assertSee('id="tableAcItems"', false);
        $sheetResponse->assertSee('id="badgeActiveArmor"', false);
        $sheetResponse->assertSee('id="badgeActiveShield"', false);
    }

    public function test_pathfinder_armors_enum_and_magic_ac_items_with_bonus_types(): void
    {
        $armors = require config_path('enums/pathfinder_armors.php');
        $this->assertIsArray($armors);
        $this->assertGreaterThanOrEqual(320, count($armors));

        // 1. Verify Mithral Shirt
        $mithral = collect($armors)->firstWhere('name', 'Mithral Shirt');
        $this->assertNotNull($mithral);
        $this->assertEquals('armor', $mithral['bonus_type']);
        $this->assertEquals(4, $mithral['bonus']);
        $this->assertEquals(6, $mithral['max_dex']);
        $this->assertEquals(0, $mithral['check_penalty']);
        $this->assertEquals(10, $mithral['weight']);

        // 2. Verify Ring of Protection
        $ring = collect($armors)->firstWhere('name', 'Ring of Protection +2');
        $this->assertNotNull($ring);
        $this->assertEquals('deflection', $ring['bonus_type']);
        $this->assertEquals(2, $ring['bonus']);
        $this->assertEquals('Rings', $ring['category']);

        // 3. Verify Amulet of Natural Armor
        $amulet = collect($armors)->firstWhere('name', 'Amulet of Natural Armor +1');
        $this->assertNotNull($amulet);
        $this->assertEquals('natural', $amulet['bonus_type']);
        $this->assertEquals(1, $amulet['bonus']);
        $this->assertEquals('Amulets', $amulet['category']);

        // 4. Verify Bracers of Armor
        $bracers = collect($armors)->firstWhere('name', 'Bracers of Armor +4');
        $this->assertNotNull($bracers);
        $this->assertEquals('armor', $bracers['bonus_type']);
        $this->assertEquals(4, $bracers['bonus']);

        // 5. Verify Shield
        $buckler = collect($armors)->firstWhere('name', 'Buckler');
        $this->assertNotNull($buckler);
        $this->assertEquals('shield', $buckler['bonus_type']);
        $this->assertEquals(1, $buckler['bonus']);

        // 6. Test saving AC items with bonus type
        $user = $this->createTestUser('actype@valiantshade.com', 'AC Type Tester');
        $hero = CharacterProfile::create([
            'character_id' => 'char_test_ac_type_' . time(),
            'user_id' => $user->id,
            'character_name' => 'AC Type Hero',
            'player_name' => $user->name,
            'race' => 'Elf',
            'class_name' => 'Wizard',
            'level' => 3,
            'sheet_data' => []
        ]);

        $saveData = [
            'id' => $hero->character_id,
            'character_id' => $hero->character_id,
            'sheetData' => [
                'acItems' => [
                    [
                        'name' => 'Mithral Shirt',
                        'type' => 'armor',
                        'bonus' => 4,
                        'pen' => 0,
                        'weight' => 10,
                        'max_dex' => 6,
                        'category' => 'Light Armors'
                    ],
                    [
                        'name' => 'Ring of Protection +2',
                        'type' => 'deflection',
                        'bonus' => 2,
                        'pen' => 0,
                        'weight' => 0,
                        'max_dex' => null,
                        'category' => 'Rings'
                    ],
                    [
                        'name' => 'Amulet of Natural Armor +1',
                        'type' => 'natural',
                        'bonus' => 1,
                        'pen' => 0,
                        'weight' => 0,
                        'max_dex' => null,
                        'category' => 'Amulets'
                    ]
                ]
            ]
        ];

        $saveResponse = $this->actingAs($user)->postJson('/valiant-shade/character/sheet/save', $saveData);
        $saveResponse->assertStatus(200);

        $hero->refresh();
        $sheetData = is_string($hero->sheet_data) ? json_decode($hero->sheet_data, true) : $hero->sheet_data;
        $innerData = $sheetData['sheetData'] ?? $sheetData;

        $this->assertCount(3, $innerData['acItems']);
        $this->assertEquals('deflection', $innerData['acItems'][1]['type']);
        $this->assertEquals('natural', $innerData['acItems'][2]['type']);
    }

    public function test_character_sheet_saves_and_persists_hp_extra_misc_feat_bonus(): void
    {
        $user = $this->createTestUser('hptest@valiantshade.com', 'HpTester');

        $hero = CharacterProfile::create([
            'character_id' => 'char_hp_test_' . time(),
            'user_id' => $user->id,
            'character_name' => 'Tough Knight',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'level' => 1,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 16, 'dex' => 12, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14,
            'hp' => 12, 'max_hp' => 12, 'hit_die' => 'd10',
            'sheet_data' => [
                'name' => 'Tough Knight',
                'race' => 'Human',
                'rank' => 'Main',
                'hpExtraMisc' => 0,
            ],
        ]);

        $saveData = [
            'id' => $hero->character_id,
            'character_id' => $hero->character_id,
            'character_name' => $hero->character_name,
            'className' => 'Paladin',
            'level' => 1,
            'hitDie' => 'd10',
            'hp' => 15,
            'maxHp' => 15,
            'sheetData' => [
                'player' => 'HpTester',
                'currentHp' => 15,
                'maxHp' => 15,
                'hpExtraMisc' => 3,
                'hpRolls' => [1 => 10],
            ]
        ];

        $saveResponse = $this->actingAs($user)->postJson('/valiant-shade/character/sheet/save', $saveData);
        $saveResponse->assertStatus(200);

        $hero->refresh();
        $sheetData = is_string($hero->sheet_data) ? json_decode($hero->sheet_data, true) : $hero->sheet_data;
        $innerData = $sheetData['sheetData'] ?? $sheetData;

        $this->assertEquals(15, $hero->max_hp);
        $this->assertEquals(15, $hero->hp);
        $this->assertEquals(3, $innerData['hpExtraMisc']);

        $viewResponse = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $hero->character_id);
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('id="sheetHpExtraMisc"', false);
        $viewResponse->assertSee('value="3"', false);
    }

    public function test_character_sheet_and_profile_saves_and_renders_three_rank_traits(): void
    {
        $user = $this->createTestUser('traittest@valiantshade.com', 'TraitHero');

        $hero = CharacterProfile::create([
            'character_id' => 'char_trait_test_' . time(),
            'user_id' => $user->id,
            'character_name' => 'Sir Lancelot',
            'race' => 'Human',
            'class_name' => 'Paladin',
            'level' => 5,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 16, 'dex' => 12, 'con' => 14, 'int' => 10, 'wis' => 12, 'cha' => 14,
            'hp' => 45, 'max_hp' => 45, 'hit_die' => 'd10',
            'sheet_data' => [
                'name' => 'Sir Lancelot',
                'race' => 'Human',
                'rank' => 'Main',
                'traits' => [
                    'uye' => ['name' => 'Reactionary', 'desc' => '+2 Initiative check bonus'],
                    'kidemli' => ['name' => 'Courageous', 'desc' => '+2 save vs fear'],
                    'uzman' => ['name' => 'Armor Master', 'desc' => '-1 Armor check penalty'],
                ],
            ],
        ]);

        $saveData = [
            'id' => $hero->character_id,
            'character_id' => $hero->character_id,
            'character_name' => $hero->character_name,
            'className' => 'Paladin',
            'level' => 5,
            'hitDie' => 'd10',
            'hp' => 45,
            'maxHp' => 45,
            'sheetData' => [
                'player' => 'TraitHero',
                'currentHp' => 45,
                'maxHp' => 45,
                'traits' => [
                    'uye' => ['name' => 'Reactionary', 'desc' => '+2 Initiative check bonus'],
                    'kidemli' => ['name' => 'Courageous', 'desc' => '+2 save vs fear'],
                    'uzman' => ['name' => 'Armor Master', 'desc' => '-1 Armor check penalty'],
                ]
            ]
        ];

        $saveResponse = $this->actingAs($user)->postJson('/valiant-shade/character/sheet/save', $saveData);
        $saveResponse->assertStatus(200);

        $hero->refresh();
        $sheetData = is_string($hero->sheet_data) ? json_decode($hero->sheet_data, true) : $hero->sheet_data;
        $innerData = $sheetData['sheetData'] ?? $sheetData;

        $this->assertArrayHasKey('traits', $innerData);
        $this->assertEquals('Reactionary', $innerData['traits']['uye']['name']);
        $this->assertEquals('Courageous', $innerData['traits']['kidemli']['name']);
        $this->assertEquals('Armor Master', $innerData['traits']['uzman']['name']);

        // Check Character Sheet View
        $sheetView = $this->actingAs($user)->get('/valiant-shade/character/sheet?id=' . $hero->character_id);
        $sheetView->assertStatus(200);
        $sheetView->assertSee('Reactionary');
        $sheetView->assertSee('Courageous');
        $sheetView->assertSee('Armor Master');
        $sheetView->assertSee('sheetTraitName_uye', false);
        $sheetView->assertSee('sheetTraitName_kidemli', false);
        $sheetView->assertSee('sheetTraitName_uzman', false);

        // Check Profile User Page View
        $profileView = $this->actingAs($user)->get('/valiant-shade/pages/profile-user');
        $profileView->assertStatus(200);
        $profileView->assertSee('navs-traits', false);
        $profileView->assertSee('tab-btn-traits', false);
        $profileView->assertSee('function escapeHtml', false);
        $profileView->assertSee('renderTraitsTab(initChar)', false);
        $profileView->assertSee('3/3 Trait', false);

        // Verify charactersData passed to the view has traits and sheetData populated
        $viewCharsData = $profileView->viewData('charactersData');
        $this->assertNotEmpty($viewCharsData);
        $firstChar = collect($viewCharsData)->firstWhere('name', 'Sir Lancelot');
        $this->assertNotNull($firstChar);
        $this->assertArrayHasKey('traits', $firstChar);
        $this->assertEquals('Reactionary', $firstChar['traits']['uye']['name']);
        $this->assertEquals('Courageous', $firstChar['traits']['kidemli']['name']);
        $this->assertEquals('Armor Master', $firstChar['traits']['uzman']['name']);
    }

    public function test_profile_user_resolves_gold_from_nested_sheet_data_money_gp(): void
    {
        $user = $this->createTestUser();

        $hero = CharacterProfile::create([
            'character_id' => 'char_gold_test_' . time(),
            'user_id' => $user->id,
            'character_name' => 'GoldHero',
            'player_name' => $user->name,
            'race' => 'Elf',
            'class_name' => 'Wizard',
            'level' => 3,
            'rank' => 'Main',
            'character_type' => 'Main',
            'str' => 10, 'dex' => 14, 'con' => 12, 'int' => 18, 'wis' => 12, 'cha' => 10,
            'hp' => 18, 'max_hp' => 18, 'hit_die' => 'd6',
            'sheet_data' => [
                'sheetData' => [
                    'moneyGp' => 450,
                    'traits' => [
                        'uye' => ['name' => 'Scholar', 'desc' => '+1 Knowledge checks'],
                        'kidemli' => ['name' => '', 'desc' => ''],
                        'uzman' => ['name' => '', 'desc' => ''],
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/profile-user');
        $response->assertStatus(200);

        $viewCharsData = $response->viewData('charactersData');
        $charData = collect($viewCharsData)->firstWhere('name', 'GoldHero');
        $this->assertNotNull($charData);
        $this->assertEquals(450, $charData['gold']);
        $this->assertEquals('Scholar', $charData['traits']['uye']['name']);
        $response->assertSee('450 GP', false);
        $response->assertSee('1/3 Trait', false);
    }

    public function test_get_claimable_characters_list_returns_json_and_filters(): void
    {
        $user = $this->createTestUser();

        $c1 = \App\Models\Character::create([
            'user_id' => $user->id,
            'name' => 'Thoma Quickfire',
            'current_level' => 12,
            'total_xp' => 161506.57,
            'character_type' => 'MAIN',
            'status' => 'ACTIVE',
            'allowance_sp' => 6560,
        ]);

        $c2 = \App\Models\Character::create([
            'user_id' => $user->id,
            'name' => 'Alden Lowlvl',
            'current_level' => 2,
            'total_xp' => 2500,
            'character_type' => 'MAIN',
            'status' => 'ACTIVE',
            'allowance_sp' => 100,
        ]);

        $response = $this->actingAs($user)->getJson('/valiant-shade/character/claimable-list?q=Thoma');
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $chars = $response->json('characters');
        $this->assertCount(1, $chars);
        $this->assertEquals('Thoma Quickfire', $chars[0]['name']);
        $this->assertEquals(12, $chars[0]['level']);
    }

    public function test_get_claimable_character_details_returns_feats_and_required_levels(): void
    {
        $user = $this->createTestUser();

        $c = \App\Models\Character::create([
            'user_id' => $user->id,
            'name' => 'Thoma Quickfire',
            'current_level' => 12,
            'total_xp' => 161506.57,
            'character_type' => 'MAIN',
            'status' => 'ACTIVE',
            'allowance_sp' => 6560,
        ]);

        \App\Models\CharacterInfo::create([
            'character_id' => $c->id,
            'class_and_level' => 'Alchemist(Grenadier)',
            'race' => 'Human',
            'strength' => 14,
            'dexterity' => 16,
            'constitution' => 14,
            'intelligence' => 18,
            'wisdom' => 12,
            'charisma' => 10,
        ]);

        \App\Models\CharacterFeat::create([
            'character_id' => $c->id,
            'character_name' => $c->name,
            'level' => 1,
            'feat_name' => 'Point-Blank Shot',
            'feat_type' => 'General',
        ]);

        $response = $this->actingAs($user)->getJson('/valiant-shade/character/claimable-details/' . $c->id);
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $charData = $response->json('character');
        $this->assertEquals(12, $charData['level']);
        $this->assertEquals('Alchemist', $charData['suggested_class']);
        $this->assertEquals('Grenadier', $charData['suggested_archetype']);
        $this->assertEquals([1, 3, 5, 7, 9, 11], $charData['required_feat_levels']);
    }

    public function test_claim_character_succeeds_without_feats_and_traits(): void
    {
        $user = $this->createTestUser();

        $c = \App\Models\Character::create([
            'user_id' => $user->id,
            'name' => 'Thoma Quickfire',
            'current_level' => 12,
            'total_xp' => 161506.57,
            'character_type' => 'MAIN',
            'status' => 'ACTIVE',
            'allowance_sp' => 6560,
        ]);

        // Attempt claim with no feats or traits provided
        $response = $this->actingAs($user)->postJson('/valiant-shade/character/claim', [
            'character_id' => $c->id,
            'race' => 'Human',
            'class_name' => 'Alchemist',
            'str' => 14, 'dex' => 16, 'con' => 14, 'int' => 18, 'wis' => 12, 'cha' => 10,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_claiming_report_character_transfers_tracker_and_preserves_legacy_xp(): void
    {
        $user = $this->createTestUser();
        $placeholder = User::factory()->create(['status' => 0, 'name' => 'Unknown 16.11.2026 1']);
        $character = new \App\Models\Character([
            'user_id' => $placeholder->id, 'name' => 'Report Hero', 'character_type' => 'MAIN',
            'status' => 'PENDING', 'current_level' => 3, 'quest_xp' => 1000,
            'report_xp' => 300, 'total_xp' => 5000,
        ]);
        $character->saveQuietly();
        \App\Models\PlayerXp::create([
            'user_id' => $placeholder->id, 'character_name' => $character->name,
            'player_name' => $placeholder->name, 'level' => 3,
            'mission_xp' => 1000, 'report_xp' => 300, 'total_xp' => 5000,
        ]);

        $this->actingAs($user)->postJson('/valiant-shade/character/claim', [
            'character_id' => $character->id, 'race' => 'Human', 'class_name' => 'Alchemist',
            'str' => 14, 'dex' => 16, 'con' => 14, 'int' => 18, 'wis' => 12, 'cha' => 10,
        ])->assertOk();

        $this->assertDatabaseHas('characters', [
            'id' => $character->id, 'user_id' => $user->id, 'status' => 'ACTIVE', 'total_xp' => 5000,
        ]);
        $this->assertDatabaseHas('player_xps', [
            'character_name' => 'Report Hero', 'user_id' => $user->id,
            'player_name' => $user->display_name, 'total_xp' => 5000,
        ]);
        $this->assertDatabaseHas('character_profiles', [
            'character_name' => 'Report Hero', 'user_id' => $user->id, 'level' => 3,
        ]);
    }

    public function test_claim_character_succeeds_with_empty_or_three_trait_names(): void
    {
        $initialOwner = $this->createTestUser('initial@valiantshade.com', 'Initial Owner');
        $user = $this->createTestUser();

        $c = \App\Models\Character::create([
            'user_id' => $initialOwner->id,
            'name' => 'Thoma Quickfire',
            'current_level' => 12,
            'total_xp' => 161506.57,
            'character_type' => 'MAIN',
            'status' => 'INACTIVE',
            'allowance_sp' => 6560,
        ]);

        // Claim with 3 trait names only (no descriptions)
        $response = $this->actingAs($user)->postJson('/valiant-shade/character/claim', [
            'character_id' => $c->id,
            'race' => 'Human',
            'class_name' => 'Alchemist',
            'archetype' => 'Grenadier',
            'str' => 14, 'dex' => 16, 'con' => 14, 'int' => 18, 'wis' => 12, 'cha' => 10,
            'traits' => [
                'trait_1_name' => 'Reactionary',
                'trait_2_name' => 'Armor Expert',
                'trait_3_name' => 'Valiant Defender',
            ],
            'feats' => [
                '1' => 'Point-Blank Shot',
                '3' => 'Precise Shot',
                '5' => 'Rapid Shot',
                '7' => 'Close-Quarters Thrower',
                '9' => 'Brew Potion',
                '11' => 'Extra Discovery',
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verify CharacterProfile created at level 12 with 3 traits
        $profile = CharacterProfile::where('character_name', 'Thoma Quickfire')->first();
        $this->assertNotNull($profile);
        $this->assertEquals(12, $profile->level);
        $this->assertEquals('Alchemist', $profile->class_name);
        $this->assertEquals('Reactionary', $profile->sheet_data['traits']['trait_1']['name']);
        $this->assertEquals('Armor Expert', $profile->sheet_data['traits']['trait_2']['name']);
        $this->assertEquals('Valiant Defender', $profile->sheet_data['traits']['trait_3']['name']);
        $this->assertEquals('Valiant Defender', $profile->sheet_data['traits']['uzman']['name']);
        $response->assertJsonPath('success', true);

        // Verify CharacterProfile created at level 12
        $profile = CharacterProfile::where('character_name', 'Thoma Quickfire')->first();
        $this->assertNotNull($profile);
        $this->assertEquals(12, $profile->level);
        $this->assertEquals('Alchemist', $profile->class_name);
        $this->assertEquals('Grenadier', $profile->archetype);
        $this->assertEquals($user->id, $profile->user_id);

        // Verify active character set on user
        $user->refresh();
        $this->assertEquals('Thoma Quickfire', $user->current_character);

        // Verify character in characters table updated
        $c->refresh();
        $this->assertEquals($user->id, $c->user_id);
        $this->assertEquals('ACTIVE', $c->status);

        // Verify feats persisted in character_feats
        $this->assertDatabaseHas('character_feats', [
            'character_id' => $c->id,
            'level' => 1,
            'feat_name' => 'Point-Blank Shot',
        ]);
        $this->assertDatabaseHas('character_feats', [
            'character_id' => $c->id,
            'level' => 11,
            'feat_name' => 'Extra Discovery',
        ]);
    }
}





