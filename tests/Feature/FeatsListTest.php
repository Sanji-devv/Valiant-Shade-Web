<?php

namespace Tests\Feature;

use App\Models\CharacterFeat;
use App\Models\CharacterProfile;
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
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feats');
        $response->assertStatus(200);
        $response->assertSee(__('Feats List'));
        $response->assertSee('Magus Buckler');
    }

    public function test_feats_page_turkish_route_alias_works(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feat-listesi');
        $response->assertStatus(200);
        $response->assertSee('Magus Buckler');
    }

    public function test_magus_buckler_dossier_loads_reference_feats(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?character=Magus Buckler');
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
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?character=Magus Buckler');
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
        $responseBalmond = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?character=Balmond');
        $responseBalmond->assertStatus(200);

        // "Normal + Sınıf" must NOT appear anywhere
        $responseBalmond->assertDontSee('Normal + Sınıf');
        $responseBalmond->assertDontSee('Magus Arcana / Bonus Feat');

        // Magus gets Bonus Feat at level 5 & 11
        $responseBalmond->assertSee('+Bonus Feat');

        // Balmond is Half-Orc, so +Human is NOT rendered
        $responseBalmond->assertDontSee('+Human');

        // 2. Check Magus Buckler (Human Magus)
        $responseHuman = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?character=Magus Buckler');
        $responseHuman->assertStatus(200);

        // Magus Buckler is Human, gets +Human badge at level 1
        $responseHuman->assertSee('+Human');
        $responseHuman->assertSee('+Bonus Feat');
        $responseHuman->assertDontSee('Normal + Sınıf');
        $responseHuman->assertDontSee('Magus Arcana / Bonus Feat');
    }

    public function test_can_switch_to_another_character_from_master_sheet(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?character=Arelion Vane');
        $response->assertStatus(200);
        $response->assertSee('Arelion Vane');
        $response->assertSee('Weapon Focus (Dagger)');
        $response->assertSee('Weapon Finesse (Dagger)');
    }

    public function test_master_feats_table_view_renders(): void
    {
        $response = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?view=table');
        $response->assertStatus(200);
        $response->assertSee(__('Master Feats Archive Table'));
        $response->assertSee('Arelion Vane');
        $response->assertSee('Argon Ranfer');
        $response->assertSee('Balmond');
    }

    public function test_can_store_new_feat(): void
    {
        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/feats', [
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
        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/feats', [
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
        $this->actingAs($this->user)->get('/valiant-shade/pages/feats'); // triggers initialization

        $feat = CharacterFeat::where('character_name', 'Magus Buckler')->first();
        $this->assertNotNull($feat);

        $response = $this->actingAs($this->user)->putJson("/valiant-shade/pages/feats/{$feat->id}", [
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

        $response = $this->actingAs($this->user)->deleteJson("/valiant-shade/pages/feats/{$feat->id}");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('character_feats', [
            'id' => $feat->id,
        ]);
    }

    public function test_can_update_character_stats(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/feats'); // triggers initialization

        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/feats/stats', [
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
            'dex'            => 20,
            'hp'             => 95,
        ]);
    }

    public function test_reset_excel_restores_defaults(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/feats'); // triggers initialization

        // Delete all feats
        CharacterFeat::query()->delete();
        $this->assertEquals(0, CharacterFeat::count());

        // Call reset
        $response = $this->actingAs($this->user)->postJson('/valiant-shade/pages/feats/reset-excel');
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
        $this->actingAs($this->user)->get('/valiant-shade/pages/feats'); // initialize

        $this->assertDatabaseHas('character_profiles', ['character_name' => 'Magus Buckler']);
        $this->assertGreaterThan(0, CharacterFeat::where('character_name', 'Magus Buckler')->count());

        $response = $this->actingAs($this->user)->deleteJson('/valiant-shade/pages/feats/character/delete', [
            'character_name' => 'Magus Buckler',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('character_profiles', ['character_name' => 'Magus Buckler']);
        $this->assertEquals(0, CharacterFeat::where('character_name', 'Magus Buckler')->count());
    }

    public function test_character_sorting_by_name_and_level(): void
    {
        $this->actingAs($this->user)->get('/valiant-shade/pages/feats'); // initialize

        // Test name sorting desc
        $responseDesc = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?sort=name_desc');
        $responseDesc->assertStatus(200);

        // Test level sorting desc
        $responseLvl = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?sort=level_desc');
        $responseLvl->assertStatus(200);

        // Test master table sorting
        $responseTable = $this->actingAs($this->user)->get('/valiant-shade/pages/feats?view=table&sort=feats_desc');
        $responseTable->assertStatus(200);
    }
}

