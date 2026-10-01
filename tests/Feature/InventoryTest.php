<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('en');
    }

    public function test_inventory_page_renders_with_seeded_items_and_silver_currency(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->get('/valiant-shade/pages/inventory');

        $response->assertStatus(200);
        $response->assertSee('Guild Inventory');
        $response->assertSee('Dragon Slaying Arrow');
        $response->assertSee('Half Plate');
        $response->assertSee('Bag of Holding');
        $response->assertSee('Büyülü Pipo');
        // Check Silver (SP) is default
        $response->assertSee('Silver (SP)');
        $response->assertSee('Copper (CP)');
        $response->assertSee('Gold (GP)');
        $response->assertSee('Platinum (PP)');
        // Guild Vault 30k Silver (30,000.00 SP)
        $response->assertSee('Guild Vault (Cash)');
        $response->assertSee('30,000.00');
        // Check removed subtitle is not present
        $response->assertDontSee('Pathfinder 1E kural setine göre parti kasası, eşya birim fiyatları');
        // Non-admin sees read-only notice
        $response->assertSee('Read Only (Admin Access Required)');
    }

    public function test_non_admin_cannot_create_or_delete_items(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        // Cannot create
        $createResponse = $this->actingAs($user)->post('/valiant-shade/pages/inventory', [
            'name' => 'Illegal Sword',
            'type' => 'Weapon',
            'quantity' => 1,
            'unit_price' => 100,
        ]);
        $createResponse->assertStatus(403);

        // Cannot reset
        $resetResponse = $this->actingAs($user)->post('/valiant-shade/pages/inventory/reset-defaults');
        $resetResponse->assertStatus(403);
    }

    public function test_admin_can_create_new_inventory_item_with_automatic_price_calculations(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $payload = [
            'name' => 'Vorpal Longsword',
            'quality' => '+5',
            'type' => 'Weapon',
            'quantity' => 2,
            'unit_price' => 50000,
            'notes' => 'Beheads on natural 20.',
        ];

        $response = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', $payload);

        $response->assertRedirect('/valiant-shade/pages/inventory');

        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Vorpal Longsword',
            'quality' => '+5',
            'quantity' => 2,
            'unit_price' => 50000,
            'total_price' => 100000,
            'market_value' => 55000, // 55% of 100,000
            'buy_price' => 27500,    // 55,000 / 2
        ]);
    }

    public function test_admin_can_update_inventory_item(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/inventory'); // Ensure seeded

        $item = InventoryItem::first();

        $updatePayload = [
            'name' => $item->name,
            'quality' => '+2',
            'type' => $item->type,
            'quantity' => 3,
            'unit_price' => 10000,
            'notes' => 'Updated test notes',
        ];

        $response = $this->actingAs($admin)->put("/valiant-shade/pages/inventory/{$item->id}", $updatePayload);

        $response->assertRedirect('/valiant-shade/pages/inventory');

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'quality' => '+2',
            'quantity' => 3,
            'unit_price' => 10000,
            'total_price' => 30000,
            'market_value' => 16500,
        ]);
    }

    public function test_admin_can_delete_inventory_item(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/inventory');

        $item = InventoryItem::first();

        $response = $this->actingAs($admin)->delete("/valiant-shade/pages/inventory/{$item->id}");

        $response->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseMissing('inventory_items', ['id' => $item->id]);
    }

    public function test_admin_can_reset_to_defaults(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        // Wipe items
        InventoryItem::truncate();
        $this->assertEquals(0, InventoryItem::count());

        $response = $this->actingAs($admin)->post('/valiant-shade/pages/inventory/reset-defaults');

        $response->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertEquals(31, InventoryItem::count());
        $this->assertEquals(1446130, (float) InventoryItem::sum('total_price'));
    }

    public function test_all_11_item_types_are_supported_and_configured(): void
    {
        $expectedTypes = [
            'Weapon',
            'Potion',
            'Ring',
            'Armor',
            'Misc',
            'Trade Goods',
            'Gear',
            'Wand',
            'Scroll',
            'Ammunition',
            'Unknown',
        ];

        // Ensure every type is defined in the TYPES constant
        foreach ($expectedTypes as $type) {
            $this->assertArrayHasKey($type, InventoryItem::TYPES);
            $this->assertNotEmpty(InventoryItem::TYPES[$type]['dot_color']);
            $this->assertNotEmpty(InventoryItem::TYPES[$type]['badge_style']);
            $this->assertNotEmpty(InventoryItem::TYPES[$type]['icon']);
        }

        $admin = User::factory()->create(['role' => 'Admin']);
        $response = $this->actingAs($admin)->get('/valiant-shade/pages/inventory');

        // All types should appear in category filter buttons
        foreach ($expectedTypes as $type) {
            $response->assertSee('data-type="' . $type . '"', false);
        }

        // Test item creation for a newly added type, e.g. Wand
        $wandResponse = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Wand of Cure Light Wounds',
            'quality' => '50 charges',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 750,
        ]);
        $wandResponse->assertRedirect('/valiant-shade/pages/inventory');

        $wand = InventoryItem::where('name', 'Wand of Cure Light Wounds')->first();
        $this->assertNotNull($wand);
        $this->assertEquals('#ffe082', $wand->type_dot_color);
        $this->assertEquals('tabler-wand', $wand->type_icon);
    }

    public function test_non_admin_cannot_update_guild_vault(): void
    {
        $user = User::factory()->create(['role' => 'User']);

        $response = $this->actingAs($user)->post('/valiant-shade/pages/inventory/vault', [
            'operation' => 'set',
            'amount_sp' => 50000,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_deposit_and_withdraw_guild_vault(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        // Set balance to 35,000 SP
        $setResponse = $this->actingAs($admin)->post('/valiant-shade/pages/inventory/vault', [
            'operation' => 'set',
            'amount_sp' => 35000,
            'notes' => 'Set test note',
        ]);
        $setResponse->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 35000,
        ]);

        // Deposit 5,000 SP (total should be 40,000)
        $depositResponse = $this->actingAs($admin)->post('/valiant-shade/pages/inventory/vault', [
            'operation' => 'deposit',
            'amount_sp' => 5000,
            'notes' => 'Quest loot reward',
        ]);
        $depositResponse->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 40000,
        ]);

        // Withdraw 10,000 SP (total should be 30,000)
        $withdrawResponse = $this->actingAs($admin)->post('/valiant-shade/pages/inventory/vault', [
            'operation' => 'withdraw',
            'amount_sp' => 10000,
            'notes' => 'Party gear purchases',
        ]);
        $withdrawResponse->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 30000,
        ]);
    }

    public function test_wand_charges_formula_calculations(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        // 1. Full 50 charges (Max 50): 750 GP base, qty 1 -> 750 GP
        $resp50 = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Wand of Cure Light Wounds (50 charges)',
            'quality' => '50 charges',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 750,
        ]);
        $resp50->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Wand of Cure Light Wounds (50 charges)',
            'total_price' => 750,
            'market_value' => 412.5,
            'buy_price' => 412.5,
        ]);

        // 2. 25 charges: 750 GP base, qty 1 -> 375 GP
        $resp25 = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Wand of Cure Light Wounds (25 charges)',
            'quality' => '25 charges',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 750,
        ]);
        $resp25->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Wand of Cure Light Wounds (25 charges)',
            'total_price' => 375,
            'market_value' => 206.25,
            'buy_price' => 206.25,
        ]);

        // 3. 10 charges with quantity 2: 1000 GP base -> (10/50)*1000*2 = 400 GP
        $resp10 = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Wand of Magic Missile (10 charges)',
            'quality' => '10 charges',
            'type' => 'Wand',
            'quantity' => 2,
            'unit_price' => 1000,
        ]);
        $resp10->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Wand of Magic Missile (10 charges)',
            'total_price' => 400,
            'market_value' => 220,
            'buy_price' => 110,
        ]);

        // 4. 1 charge (Min 1): 750 GP base, qty 1 -> (1/50)*750 = 15 GP
        $resp1 = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Wand of Shocking Grasp (1 charge)',
            'quality' => '1 charge',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 750,
        ]);
        $resp1->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Wand of Shocking Grasp (1 charge)',
            'total_price' => 15,
            'market_value' => 8.25,
            'buy_price' => 8.25,
        ]);

        // 5. 0 charges: price is 0
        $resp0 = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Depleted Wand (0 charges)',
            'quality' => '0 charges',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 750,
        ]);
        $resp0->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Depleted Wand (0 charges)',
            'total_price' => 0,
            'market_value' => 0,
            'buy_price' => 0,
        ]);

        // 6. Over max (80 charges): clamped to 50 charges
        $respOver = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Overcharged Wand (80 charges)',
            'quality' => '80 charges',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 750,
        ]);
        $respOver->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Overcharged Wand (80 charges)',
            'total_price' => 750,
        ]);

        // 7. Non-Wand item with 'charge' in quality (e.g. Gear with '7 charge'):
        // Must use normal formula (unit_price * qty)
        $respGear = $this->actingAs($admin)->post('/valiant-shade/pages/inventory', [
            'name' => 'Necklace of Fireballs',
            'quality' => '7 charge',
            'type' => 'Gear',
            'quantity' => 1,
            'unit_price' => 58500,
        ]);
        $respGear->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Necklace of Fireballs',
            'total_price' => 58500,
            'market_value' => 32175,
        ]);
    }

    public function test_admin_can_update_wand_charges_and_price_recalculates(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $wand = InventoryItem::create([
            'name' => 'Wand of Fireball',
            'quality' => '50 charges',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 11250,
            'total_price' => 11250,
            'market_value' => 6187.5,
            'buy_price' => 6187.5,
        ]);

        // Update to 20 charges: (20/50) * 11250 = 4500 GP
        $response = $this->actingAs($admin)->put("/valiant-shade/pages/inventory/{$wand->id}", [
            'name' => 'Wand of Fireball',
            'quality' => '20 charges',
            'type' => 'Wand',
            'quantity' => 1,
            'unit_price' => 11250,
        ]);

        $response->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'id' => $wand->id,
            'quality' => '20 charges',
            'total_price' => 4500,
            'market_value' => 2475,
            'buy_price' => 2475,
        ]);
    }
}


