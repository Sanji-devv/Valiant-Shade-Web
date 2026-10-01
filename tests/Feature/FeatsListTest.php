<?php

namespace Tests\Feature;

use App\Models\CharacterFeat;
use App\Models\CharacterProfile;
use App\Models\CharacterTrait;
use App\Models\PathfinderTrait;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatsListTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'User']);
    }

    public function test_feats_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait');
        $response->assertStatus(200);
        $response->assertSee(__('Feats List'));
        $response->assertSee('Magus Buckler');
    }

    public function test_legacy_feats_page_redirects_to_featntrait(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feats');
        $response->assertRedirect('/valiant-shade/pages/featntrait');

        $responseQuery = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?character=Magus%20Buckler');
        $responseQuery->assertRedirect('/valiant-shade/pages/featntrait?character=Magus%20Buckler');
    }

    public function test_feats_page_turkish_route_alias_redirects(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feat-listesi');
        $response->assertRedirect('/valiant-shade/pages/featntrait');
    }

    public function test_magus_buckler_dossier_loads_reference_feats(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?character=Magus Buckler');
        $response->assertStatus(200);

        // Check reference feats from Image 2
        $response->assertSee('Magus Buckler');
        $response->assertSee('Focused Mind');
        $response->assertSee('Magical Knack');
        $response->assertSee('Weapon Focus (Rapier)');
        $response->assertSee('Weapon Finesse (Rapier)');
        $response->assertSee('Improved Critical (Rapier)');
        $response->assertSee('Magus Arcana (Concentrate)');
        $response->assertSee('Weapon Specialization (Rapier)');
        $response->assertSee('Magus Arcana (Close Range)');
        $response->assertSee('Spell Specialization (Shocking Grasp)');
        $response->assertSee('Intensified Spell');
        $response->assertSee('Sınıf Yeteneği');
        $response->assertSee('Sınıf Bonusu');
    }

    public function test_class_talents_and_bonus_feats_rendered_in_feat_name_column(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?character=Magus Buckler');
        $response->assertStatus(200);

        // Header includes clean simplified columns
        $response->assertSee('FEAT SEVİYESİ');
        $response->assertSee('FEAT ADI');
        $response->assertSee('EKLE');

        // Active level radiance styling
        $response->assertSee('row-active-level');

        // Class talents and bonus feats rendered with badges
        $response->assertSee('Magus Arcana (Concentrate)');
        $response->assertSee('Magus Arcana (Close Range)');
        $response->assertSee('Advanced Weapon Training (Warrior Spirit)');
        $response->assertSee('Weapon Focus (Rapier)');
        $response->assertSee('Quick Draw');
    }

    public function test_level_cell_shows_only_bonus_feat_and_human_badges_without_normal_labels(): void
    {
        // 1. Check Balmond (Half-Orc Magus Level 8)
        $responseBalmond = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?character=Balmond');
        $responseBalmond->assertStatus(200);

        // "Normal + Sınıf" must NOT appear anywhere
        $responseBalmond->assertDontSee('Normal + Sınıf');
        $responseBalmond->assertDontSee('Magus Arcana / Bonus Feat');

        // Magus gets Bonus Feat at level 5 & 11
        $responseBalmond->assertSee('+Bonus Feat');

        // Balmond is Half-Orc, so +Human is NOT rendered
        $responseBalmond->assertDontSee('+Human');

        // 2. Check Magus Buckler (Human Magus)
        $responseHuman = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?character=Magus Buckler');
        $responseHuman->assertStatus(200);

        // Magus Buckler is Human, gets +Human badge at level 1
        $responseHuman->assertSee('+Human');
        $responseHuman->assertSee('+Bonus Feat');
        $responseHuman->assertDontSee('Normal + Sınıf');
        $responseHuman->assertDontSee('Magus Arcana / Bonus Feat');
    }

    public function test_can_switch_to_another_character_from_master_sheet(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?character=Arelion Vane');
        $response->assertStatus(200);
        $response->assertSee('Arelion Vane');
        $response->assertSee('Weapon Focus (Dagger)');
        $response->assertSee('Weapon Finesse (Dagger)');
    }

    public function test_master_feats_table_view_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?view=table');
        $response->assertStatus(200);
        $response->assertSee(__('Master Feats Archive Table'));
        $response->assertSee('Arelion Vane');
        $response->assertSee('Argon Ranfer');
        $response->assertSee('Balmond');
    }

    public function test_can_store_new_feat(): void
    {
        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/featntrait', [
            'character_name' => 'Magus Buckler',
            'level'          => 13,
            'slot_type'      => 'standard',
            'feat_name'      => 'Quicken Spell',
            'category'       => 'Metamagic',
            'prerequisites'  => 'None',
            'notes'          => 'For emergency true strike or shield',
            'is_class_feat'  => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('character_feats', [
            'character_name' => 'Magus Buckler',
            'level'          => 13,
            'feat_name'      => 'Quicken Spell',
        ]);
    }

    public function test_can_store_class_talent(): void
    {
        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/featntrait', [
            'character_name' => 'Arelion Vane',
            'level'          => 4,
            'slot_type'      => 'class_talent',
            'feat_name'      => 'Rogue Talent (Combat Trick - Combat Reflexes)',
            'category'       => 'Rogue Talent',
            'is_class_feat'  => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('character_feats', [
            'character_name' => 'Arelion Vane',
            'level'          => 4,
            'slot_type'      => 'class_talent',
            'feat_name'      => 'Rogue Talent (Combat Trick - Combat Reflexes)',
        ]);
    }

    public function test_can_update_feat(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // triggers initialization

        $feat = CharacterFeat::where('character_name', 'Magus Buckler')->first();
        $this->assertNotNull($feat);

        $response = $this->actingAs($this->user)->putJson("/valiant-shade/pages/featntrait/{$feat->id}", [
            'feat_name' => 'Updated Feat Name',
            'notes'     => 'Testing notes update',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('character_feats', [
            'id'        => $feat->id,
            'feat_name' => 'Updated Feat Name',
        ]);
    }

    public function test_can_delete_feat(): void
    {
        $feat = CharacterFeat::create([
            'character_name' => 'Magus Buckler',
            'level'          => 20,
            'slot_type'      => 'reserve',
            'feat_name'      => 'Temporary Feat to Delete',
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/valiant-shade/pages/featntrait/{$feat->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('character_feats', [
            'id' => $feat->id,
        ]);
    }

    public function test_can_update_character_stats(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // triggers initialization

        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/featntrait/stats', [
            'character_name' => 'Magus Buckler',
            'race'           => 'Human',
            'class_name'     => 'Magus',
            'archetype'      => 'Bladebound + Kensai',
            'level'          => 13,
            'str'            => 10,
            'dex'            => 20,
            'con'            => 14,
            'int'            => 19,
            'wis'            => 12,
            'cha'            => 10,
            'hp'             => 95,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('character_profiles', [
            'character_name' => 'Magus Buckler',
            'level'          => 13,
        ]);

        $profile = CharacterProfile::where('character_name', 'Magus Buckler')->first();
        $this->assertNotNull($profile);
        $this->assertEquals(20, $profile->dex);
        $this->assertEquals(95, $profile->hp);
    }

    public function test_reset_restores_defaults(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // triggers initialization

        // Delete all feats
        CharacterFeat::query()->delete();
        $this->assertEquals(0, CharacterFeat::count());

        // Call reset
        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/featntrait/reset-defaults');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Feats restored
        $this->assertGreaterThan(50, CharacterFeat::count());
        $this->assertDatabaseHas('character_feats', [
            'character_name' => 'Magus Buckler',
            'feat_name'      => 'Intensified Spell',
        ]);
    }

    public function test_can_delete_character_and_their_feats(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // initialize

        $this->assertDatabaseHas('character_profiles', ['character_name' => 'Magus Buckler']);
        $this->assertGreaterThan(0, CharacterFeat::where('character_name', 'Magus Buckler')->count());

        $response = $this->actingAs($this->user)->deleteJson('/valiant-shade/pages/featntrait/character/delete', [
            'character_name' => 'Magus Buckler',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('character_profiles', ['character_name' => 'Magus Buckler']);
        $this->assertEquals(0, CharacterFeat::where('character_name', 'Magus Buckler')->count());
    }

    public function test_character_sorting_by_name_and_level(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // initialize

        // Test name sorting desc
        $responseDesc = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?sort=name_desc');
        $responseDesc->assertStatus(200);

        // Test level sorting desc
        $responseLvl = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?sort=level_desc');
        $responseLvl->assertStatus(200);

        // Test master table sorting
        $responseTable = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?view=table&sort=feats_desc');
        $responseTable->assertStatus(200);
    }

    public function test_feat_and_traits_page_renders_traits_mini_table_with_three_slots(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // initialize

        // New route alias
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feat-and-traits');
        $response->assertRedirect('/valiant-shade/pages/featntrait');

        $responsePage = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait');
        $responsePage->assertStatus(200);

        // Name and traits mini table
        $responsePage->assertSee(__('Feat and Traits'));
        $responsePage->assertSee('Karakter Traitleri (Lonca Rütbe Gelişimi)');
        $responsePage->assertSee('cardGuildTraits');
        $responsePage->assertSee('traitsTable');

        // Check the 3 canonical slots
        $responsePage->assertSee('Üye Traiti (1. Slot)');
        $responsePage->assertSee('Kıdemli Traiti (2. Slot)');
        $responsePage->assertSee('Uzman Traiti (3. Slot)');
    }

    public function test_traits_unlock_progression_based_on_guild_rank(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // initialize

        // 1. Üye rank character (e.g. Magus Buckler with rank 'Üye')
        $profileUye = CharacterProfile::where('character_name', 'Magus Buckler')->first();
        $profileUye->update(['rank' => 'Üye']);

        $resUye = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?character=Magus Buckler');
        $resUye->assertStatus(200);

        $traitsService = app(\App\ValiantShade\Services\CharacterTraitsService::class);
        $progUye = $traitsService->getCharacterTraitsProgression('Magus Buckler', $profileUye);

        $this->assertTrue($progUye['uye']['is_unlocked'], 'Üye slot must be unlocked for Üye rank');
        $this->assertFalse($progUye['kidemli']['is_unlocked'], 'Kıdemli slot must be locked for Üye rank');
        $this->assertFalse($progUye['uzman']['is_unlocked'], 'Uzman slot must be locked for Üye rank');

        // 2. Kıdemli rank character
        $profileUye->update(['rank' => 'Kıdemli']);
        $progKidemli = $traitsService->getCharacterTraitsProgression('Magus Buckler', $profileUye->fresh());

        $this->assertTrue($progKidemli['uye']['is_unlocked'], 'Üye slot must be unlocked for Kıdemli rank');
        $this->assertTrue($progKidemli['kidemli']['is_unlocked'], 'Kıdemli slot must be unlocked for Kıdemli rank');
        $this->assertFalse($progKidemli['uzman']['is_unlocked'], 'Uzman slot must be locked for Kıdemli rank');

        // 3. Uzman rank character
        $profileUye->update(['rank' => 'Uzman']);
        $progUzman = $traitsService->getCharacterTraitsProgression('Magus Buckler', $profileUye->fresh());

        $this->assertTrue($progUzman['uye']['is_unlocked'], 'Üye slot must be unlocked for Uzman rank');
        $this->assertTrue($progUzman['kidemli']['is_unlocked'], 'Kıdemli slot must be unlocked for Uzman rank');
        $this->assertTrue($progUzman['uzman']['is_unlocked'], 'Uzman slot must be unlocked for Uzman rank');

        // 4. Aday rank character (hierarchy 1)
        $profileUye->update(['rank' => 'Aday']);
        $progAday = $traitsService->getCharacterTraitsProgression('Magus Buckler', $profileUye->fresh());

        $this->assertFalse($progAday['uye']['is_unlocked'], 'Üye slot must be locked for Aday rank');
        $this->assertFalse($progAday['kidemli']['is_unlocked'], 'Kıdemli slot must be locked for Aday rank');
        $this->assertFalse($progAday['uzman']['is_unlocked'], 'Uzman slot must be locked for Aday rank');
    }

    public function test_save_trait_endpoint_enforces_guild_rank_check(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // initialize

        $profile = CharacterProfile::where('character_name', 'Magus Buckler')->first();
        $profile->update(['rank' => 'Üye']); // Only 'uye' slot is unlocked

        // Attempt to save to 'uye' slot -> should succeed (200)
        $resOk = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/save', [
            'character_name' => 'Magus Buckler',
            'slot_key'       => 'uye',
            'trait_name'     => 'Reactionary',
            'category'       => 'Combat',
            'description'    => '+2 bonus to initiative checks',
            'source_url'     => 'https://www.d20pfsrd.com/traits/combat-traits/reactionary/',
        ]);

        $resOk->assertStatus(200);
        $resOk->assertJson(['success' => true]);

        $this->assertDatabaseHas('character_traits', [
            'character_name' => 'Magus Buckler',
            'slot_key'       => 'uye',
            'trait_name'     => 'Reactionary',
        ]);

        // Attempt to save to 'kidemli' slot -> should fail with 422 because rank is Üye
        $resKidemliFail = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/save', [
            'character_name' => 'Magus Buckler',
            'slot_key'       => 'kidemli',
            'trait_name'     => 'Carefully Hidden',
            'category'       => 'Regional',
        ]);

        $resKidemliFail->assertStatus(422);
        $resKidemliFail->assertJson(['success' => false]);

        // Now promote character to Uzman
        $profile->update(['rank' => 'Uzman']);

        // Now saving to 'kidemli' and 'uzman' should succeed
        $resUzmanOk = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/save', [
            'character_name' => 'Magus Buckler',
            'slot_key'       => 'uzman',
            'trait_name'     => 'Magical Lineage',
            'category'       => 'Magic',
        ]);

        $resUzmanOk->assertStatus(200);
        $this->assertDatabaseHas('character_traits', [
            'character_name' => 'Magus Buckler',
            'slot_key'       => 'uzman',
            'trait_name'     => 'Magical Lineage',
        ]);
    }

    public function test_clear_trait_endpoint_clears_assigned_trait(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // initialize

        $profile = CharacterProfile::where('character_name', 'Magus Buckler')->first();
        $profile->update(['rank' => 'Üye']);

        // Assign first
        $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/save', [
            'character_name' => 'Magus Buckler',
            'slot_key'       => 'uye',
            'trait_name'     => 'Reactionary',
        ]);

        // Clear it
        $resClear = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/clear', [
            'character_name' => 'Magus Buckler',
            'slot_key'       => 'uye',
        ]);

        $resClear->assertStatus(200);
        $resClear->assertJson(['success' => true]);

        $dbTrait = CharacterTrait::where('character_name', 'Magus Buckler')
            ->where('slot_key', 'uye')
            ->first();

        $this->assertNull($dbTrait->trait_name);
    }

    public function test_traits_search_endpoint_searches_catalog(): void
    {
        $response = $this->actingAs($this->user)->getJson('/valiant-shade/pages/traits/search?q=Reactionary');
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $response->assertJsonFragment(['name' => 'Reactionary']);
    }

    public function test_thoma_quickfire_uzman_rank_unlocks_all_three_traits(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait'); // initialize demo seeds

        (new \Database\Seeders\RankSeeder())->run();

        $uzmanRank = \App\Models\Rank::where('name', 'Uzman')->first();

        // Ensure Thoma Quickfire exists in Character and CharacterProfile with rank Uzman
        $char = \App\Models\Character::firstOrCreate(
            ['name' => 'Thoma Quickfire'],
            ['rank_id' => $uzmanRank->id, 'level' => 12, 'user_id' => $this->user->id]
        );
        $char->rank_id = $uzmanRank->id;
        $char->save();

        $profile = \App\Models\CharacterProfile::firstOrCreate(
            ['character_name' => 'Thoma Quickfire'],
            [
                'user_id' => $this->user->id,
                'class_name' => 'Alchemist',
                'level' => 12,
                'rank' => 'Uzman',
                'character_type' => 'Main',
            ]
        );
        $profile->rank = 'Uzman';
        $profile->character_id = $char->id;
        $profile->save();

        $traitsService = app(\App\ValiantShade\Services\CharacterTraitsService::class);
        $progression = $traitsService->getCharacterTraitsProgression('Thoma Quickfire', $profile);

        $this->assertTrue($progression['uye']['is_unlocked'], 'Üye slot should be unlocked for Uzman');
        $this->assertTrue($progression['kidemli']['is_unlocked'], 'Kıdemli slot should be unlocked for Uzman');
        $this->assertTrue($progression['uzman']['is_unlocked'], 'Uzman slot should be unlocked for Uzman');

        // Verify page renders with all slots unlocked
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/featntrait?character=Thoma+Quickfire');
        $response->assertStatus(200);
        $response->assertSee('Thoma Quickfire');
        $response->assertSee('Uzman');

        // Test saving to all 3 slots
        foreach (['uye', 'kidemli', 'uzman'] as $slot) {
            $saveRes = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/save', [
                'character_name' => 'Thoma Quickfire',
                'slot_key'       => $slot,
                'trait_name'     => "Trait for {$slot}",
                'category'       => 'Combat',
                'description'    => "Description for {$slot}",
            ]);
            $saveRes->assertStatus(200);
            $saveRes->assertJson(['success' => true]);
        }
    }

    public function test_updates_on_pages_feats_immediately_reflect_on_character_sheet_id_501(): void
    {
        $uzmanRank = \App\Models\Rank::firstOrCreate(
            ['name' => 'Uzman'],
            ['hierarchy_order' => 5, 'hierarchy_level' => 5, 'name_tr' => 'Uzman']
        );

        $char = \App\Models\Character::firstOrCreate(
            ['id' => 501],
            ['name' => 'Thoma Quickfire', 'rank_id' => $uzmanRank->id, 'current_level' => 12, 'user_id' => $this->user->id]
        );
        $char->update(['rank_id' => $uzmanRank->id, 'current_level' => 12]);

        $profile = \App\Models\CharacterProfile::firstOrCreate(
            ['character_name' => 'Thoma Quickfire'],
            [
                'user_id' => $this->user->id,
                'character_id' => '501',
                'class_name' => 'Alchemist',
                'level' => 12,
                'rank' => 'Uzman',
                'character_type' => 'Main',
            ]
        );
        $profile->update([
            'character_id' => '501',
            'rank' => 'Uzman',
            'level' => 12,
            'class_name' => 'Alchemist',
            'sheet_data' => [
                'name' => 'Thoma Quickfire',
                'level' => 12,
                'sheetData' => [
                    'traits' => [],
                    'feats' => [],
                    'abilityScores' => ['str' => 10, 'dex' => 18, 'con' => 14, 'int' => 16, 'wis' => 10, 'cha' => 8]
                ]
            ]
        ]);

        // 1. Assign traits via /pages/traits/save on Feats & Traits page
        $traitRes1 = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/save', [
            'character_name' => 'Thoma Quickfire',
            'slot_key'       => 'uye',
            'trait_name'     => 'Reactionary',
            'category'       => 'Combat',
            'description'    => '+2 bonus to initiative checks',
        ]);
        $traitRes1->assertStatus(200);

        $traitRes2 = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/save', [
            'character_name' => 'Thoma Quickfire',
            'slot_key'       => 'uzman',
            'trait_name'     => 'Armor Master',
            'category'       => 'Armor',
            'description'    => '-1 armor check penalty',
        ]);
        $traitRes2->assertStatus(200);

        // 2. Add a feat via /pages/featntrait on Feats & Traits page
        $featRes = $this->actingAs($this->user)->postJson('/valiant-shade/pages/featntrait', [
            'character_name' => 'Thoma Quickfire',
            'level'          => 1,
            'slot_type'      => 'standard',
            'feat_name'      => 'Point-Blank Shot',
            'category'       => 'Combat',
            'notes'          => '+1 attack and damage within 30 ft.',
        ]);
        $featRes->assertStatus(200);

        // 3. Update character stats via /pages/featntrait/stats on Feats & Traits page
        $statsRes = $this->actingAs($this->user)->postJson('/valiant-shade/pages/featntrait/stats', [
            'character_name' => 'Thoma Quickfire',
            'race'           => 'Elf',
            'class_name'     => 'Alchemist',
            'level'          => 12,
            'str'            => 12,
            'dex'            => 20,
            'con'            => 14,
            'int'            => 18,
            'wis'            => 12,
            'cha'            => 8,
            'hp'             => 78,
            'notes'          => 'Master Alchemist of Valiant Shade',
        ]);
        $statsRes->assertStatus(200);

        // 4. Request the character sheet: http://127.0.0.1:8000/valiant-shade/character/sheet?id=501
        $sheetRes = $this->actingAs($this->user)->get('/valiant-shade/character/sheet?id=501');
        $sheetRes->assertStatus(200);

        // Assert traits immediately reflected on character sheet
        $sheetRes->assertSee('Reactionary');
        $sheetRes->assertSee('Armor Master');
        $sheetRes->assertSee('value="Reactionary"', false);
        $sheetRes->assertSee('value="Armor Master"', false);

        // Assert feat immediately reflected on character sheet
        $sheetRes->assertSee('Point-Blank Shot');
        $sheetRes->assertSee('+1 attack and damage within 30 ft.');

        // Assert stats immediately reflected on character sheet
        $viewData = $sheetRes->viewData('dbCharacter');
        $this->assertNotNull($viewData, 'dbCharacter should be passed to the view');
        $this->assertEquals('Thoma Quickfire', $viewData->character_name);
        $this->assertEquals(20, $viewData->dex);
        $this->assertEquals(78, $viewData->hp);
        $this->assertEquals('Master Alchemist of Valiant Shade', $viewData->notes);

        // Verify sheet_data is synchronized
        $sheet = $viewData->sheet_data;
        $this->assertEquals('Reactionary', $sheet['traits']['uye']['name']);
        $this->assertEquals('Armor Master', $sheet['traits']['uzman']['name']);
        $this->assertEquals(20, $sheet['sheetData']['abilityScores']['dex']);
        $this->assertEquals(78, $sheet['hp']);

        // 5. Test clearing a trait on /pages/traits/clear and verify sheet immediately reflects
        $clearRes = $this->actingAs($this->user)->postJson('/valiant-shade/pages/traits/clear', [
            'character_name' => 'Thoma Quickfire',
            'slot_key'       => 'uye',
        ]);
        $clearRes->assertStatus(200);

        $sheetResAfterClear = $this->actingAs($this->user)->get('/valiant-shade/character/sheet?id=501');
        $sheetResAfterClear->assertStatus(200);
        $viewDataAfterClear = $sheetResAfterClear->viewData('dbCharacter');
        $this->assertEquals('', $viewDataAfterClear->sheet_data['traits']['uye']['name']);
    }

    public function test_legacy_feats_post_and_put_endpoints_remain_functional(): void
    {
        // Test legacy POST /valiant-shade/pages/feats
        $postRes = $this->actingAs($this->user)->postJson('/valiant-shade/pages/feats', [
            'character_name' => 'Magus Buckler',
            'level'          => 15,
            'slot_type'      => 'reserve',
            'feat_name'      => 'Legacy Endpoint Feat',
        ]);
        $postRes->assertStatus(200);
        $postRes->assertJson(['success' => true]);

        $feat = CharacterFeat::where('feat_name', 'Legacy Endpoint Feat')->first();
        $this->assertNotNull($feat);

        // Test legacy PUT /valiant-shade/pages/feats/{id}
        $putRes = $this->actingAs($this->user)->putJson("/valiant-shade/pages/feats/{$feat->id}", [
            'feat_name' => 'Legacy Endpoint Feat Updated',
        ]);
        $putRes->assertStatus(200);
        $putRes->assertJson(['success' => true]);

        // Test legacy DELETE /valiant-shade/pages/feats/{id}
        $delRes = $this->actingAs($this->user)->deleteJson("/valiant-shade/pages/feats/{$feat->id}");
        $delRes->assertStatus(200);
        $delRes->assertJson(['success' => true]);
    }
}

