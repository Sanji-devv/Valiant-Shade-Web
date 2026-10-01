<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraitsCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_traits_endpoint_returns_catalog()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('valiant-shade.character.traits'));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'traits' => [
                '*' => ['name', 'category', 'url']
            ],
            'total'
        ]);

        $this->assertGreaterThan(1000, $response->json('total'));

        // Verify Briar Bandit (Forest) has category Nature (from user's recent edit)
        $traits = collect($response->json('traits'));
        $briar = $traits->firstWhere('name', 'Briar Bandit (Forest)');
        $this->assertNotNull($briar);
        $this->assertEquals('Nature', $briar['category']);

        // Verify Reactionary
        $reactionary = $traits->firstWhere('name', 'Reactionary');
        $this->assertNotNull($reactionary);
        $this->assertEquals('Combat', $reactionary['category']);
    }

    public function test_claim_character_with_fast_filled_traits()
    {
        $user = User::factory()->create();

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Kaelen Swiftblade',
            'current_level' => 3,
            'total_xp' => 5000,
            'character_type' => 'MAIN',
        ]);

        $payload = [
            'character_id' => $character->id,
            'race' => 'Elf',
            'class_name' => 'Rogue',
            'archetype' => 'Scout',
            'str' => 12,
            'dex' => 18,
            'con' => 14,
            'int' => 14,
            'wis' => 10,
            'cha' => 10,
            'traits' => [
                'trait_1_name' => 'Reactionary',
                'trait_1_desc' => '',
                'trait_2_name' => 'Carefully Hidden',
                'trait_2_desc' => '',
                'trait_3_name' => 'Briar Bandit (Forest)',
                'trait_3_desc' => '',
                'rank_trait_name' => 'Briar Bandit (Forest)',
                'rank_trait_desc' => '',
            ],
            'feats' => [
                1 => 'Weapon Finesse',
                3 => 'Dodge',
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('valiant-shade.character.claim'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $profile = CharacterProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('Kaelen Swiftblade', $profile->character_name);

        $charTraits = $profile->sheet_data['traits'] ?? [];
        $this->assertEquals('Reactionary', $charTraits['trait_1']['name'] ?? null);
        $this->assertEquals('Carefully Hidden', $charTraits['trait_2']['name'] ?? null);
        $this->assertEquals('Briar Bandit (Forest)', $charTraits['trait_3']['name'] ?? null);
    }

    public function test_claim_character_without_traits_and_feats_succeeds()
    {
        $user = User::factory()->create();

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Almira Shadowstep',
            'current_level' => 5,
            'total_xp' => 15000,
            'character_type' => 'MAIN',
        ]);

        $payload = [
            'character_id' => $character->id,
            'race' => 'Human',
            'class_name' => 'Fighter',
            'archetype' => '',
            'str' => 16,
            'dex' => 14,
            'con' => 14,
            'int' => 12,
            'wis' => 12,
            'cha' => 10,
        ];

        $response = $this->actingAs($user)->postJson(route('valiant-shade.character.claim'), $payload);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $profile = CharacterProfile::where('user_id', $user->id)->first();
        $this->assertNotNull($profile);
        $this->assertEquals('Almira Shadowstep', $profile->character_name);
        $this->assertEquals($character->current_level, $profile->level);
        $this->assertEquals(16, $profile->str);
    }
}
