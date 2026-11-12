<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
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
        $response->assertDontSee('Value (55% total');
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
        $response->assertSee('Read Only (Inventory Access Required)');
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

        $multipleCreateResponse = $this->actingAs($user)->postJson('/valiant-shade/pages/inventory', [
            'items' => [[
                'name' => 'Illegal Bulk Sword',
                'type' => 'Weapon',
                'quantity' => 1,
                'unit_price' => 100,
            ]],
        ]);
        $multipleCreateResponse->assertForbidden();

        $bulkDeleteResponse = $this->actingAs($user)->deleteJson('/valiant-shade/pages/inventory/bulk', [
            'ids' => [1],
        ]);
        $bulkDeleteResponse->assertForbidden();

        $sellResponse = $this->actingAs($user)->postJson('/valiant-shade/pages/inventory/sell', [
            'ids' => [1],
        ]);
        $sellResponse->assertForbidden();

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
            'sell_price' => 25000,   // 50% / unit
        ]);
    }

    public function test_inventory_bailiff_can_add_one_or_multiple_items_with_calculated_prices(): void
    {
        $permission = Permission::findOrCreate('inventory.all', 'web');
        $bailiff = User::factory()->create(['role' => 'BAILIFF_INVENTORY']);
        $bailiff->givePermissionTo($permission);

        $pageResponse = $this->actingAs($bailiff)->get('/valiant-shade/pages/inventory');
        $pageResponse->assertOk()
            ->assertSee('title="Add items"', false)
            ->assertSee('id="btnAddItemRow"', false)
            ->assertSee('aria-label="Delete selected items"', false)
            ->assertSee('id="btnSellSelected"', false)
            ->assertSee('Sell (50% / unit');

        $response = $this->actingAs($bailiff)->postJson('/valiant-shade/pages/inventory', [
            'items' => [
                [
                    'name' => 'Bulk Shield',
                    'quality' => '',
                    'type' => 'Armor',
                    'quantity' => 2,
                    'unit_price' => 1000,
                ],
                [
                    'name' => 'Bulk Wand',
                    'quality' => '25 charges',
                    'type' => 'Wand',
                    'quantity' => 2,
                    'unit_price' => 1000,
                ],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('success', true);
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Bulk Shield',
            'quality' => null,
            'total_price' => 2000,
            'buy_price' => 550,
            'sell_price' => 500,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Bulk Wand',
            'total_price' => 1000,
            'buy_price' => 275,
            'sell_price' => 250,
        ]);

        $ids = InventoryItem::whereIn('name', ['Bulk Shield', 'Bulk Wand'])->pluck('id')->all();
        $deleteResponse = $this->actingAs($bailiff)->deleteJson('/valiant-shade/pages/inventory/bulk', [
            'ids' => $ids,
        ]);
        $deleteResponse->assertOk()->assertJsonPath('deletedCount', 2);
        foreach ($ids as $id) {
            $this->assertSoftDeleted('inventory_items', ['id' => $id]);
        }

        $formResponse = $this->actingAs($bailiff)->post('/valiant-shade/pages/inventory', [
            'items' => [[
                'name' => 'Form Bulk Item',
                'quality' => null,
                'type' => 'Gear',
                'quantity' => 3,
                'unit_price' => 20,
            ]],
        ]);
        $formResponse->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Form Bulk Item',
            'quantity' => 3,
            'total_price' => 60,
        ]);
    }

    public function test_item_creation_rejects_empty_lists_and_accepts_more_than_100_items(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $emptyResponse = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory', [
            'items' => [],
        ]);
        $emptyResponse->assertUnprocessable()->assertJsonValidationErrors('items');

        $largeBatchResponse = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory', [
            'items' => array_fill(0, 101, [
                'name' => 'Bulk Item',
                'type' => 'Gear',
                'quantity' => 1,
                'unit_price' => 1,
            ]),
        ]);
        $largeBatchResponse->assertCreated()->assertJsonCount(101, 'items');
    }

    public function test_bulk_delete_can_empty_inventory_without_reseeding_defaults(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/inventory');
        $ids = InventoryItem::pluck('id')->all();

        $response = $this->actingAs($admin)->deleteJson('/valiant-shade/pages/inventory/bulk', [
            'ids' => $ids,
        ]);
        $response->assertOk()->assertJsonPath('deletedCount', count($ids));

        $pageResponse = $this->actingAs($admin)->get('/valiant-shade/pages/inventory');
        $pageResponse->assertOk()->assertSee('No items found in inventory');
        $this->assertSame(0, InventoryItem::count());
    }

    public function test_admin_can_sell_selected_items_and_credit_50_percent_to_guild_vault(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/inventory');

        $inventoryService = app(\App\ValiantShade\Services\InventoryService::class);
        $firstItem = $inventoryService->createItem([
            'name' => 'Sale Test Sword',
            'type' => 'Weapon',
            'quantity' => 2,
            'unit_price' => 100,
        ]);
        $secondItem = $inventoryService->createItem([
            'name' => 'Sale Test Potion',
            'type' => 'Potion',
            'quantity' => 3,
            'unit_price' => 20,
        ]);

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/sell', [
            'ids' => [$firstItem->id, $secondItem->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('soldCount', 2)
            ->assertJsonPath('proceedsSp', 1300);
        $this->assertSoftDeleted('inventory_items', ['id' => $firstItem->id]);
        $this->assertSoftDeleted('inventory_items', ['id' => $secondItem->id]);
        $this->assertDatabaseHas('guild_vaults', ['id' => 1, 'balance_sp' => 31300]);
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
        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
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
        $this->assertCount(count($expectedTypes), array_unique(array_column(InventoryItem::TYPES, 'dot_color')));
        $this->assertSame('#ef4444', InventoryItem::TYPES['Unknown']['dot_color']);
        $this->assertStringContainsString('color: #ef4444;', InventoryItem::TYPES['Unknown']['badge_style']);

        $admin = User::factory()->create(['role' => 'Admin']);
        $response = $this->actingAs($admin)->get('/valiant-shade/pages/inventory');
        $pageContent = $response->getContent();

        $response->assertSee('id="btnAddItems"', false)
            ->assertSee('title="Add items"', false)
            ->assertSee('id="btnAddItemRow"', false)
            ->assertSee('action="' . route('valiant-shade.pages-inventory.store') . '"', false)
            ->assertSee('id="btnDeleteSelected"', false)
            ->assertDontSee('id="modalAddItem"', false)
            ->assertDontSee('btn-delete-item')
            ->assertSee('class="d-flex flex-nowrap align-items-center gap-2 ms-auto"', false)
            ->assertDontSee('id="btnExportCsv"', false)
            ->assertDontSee('id="btnResetDefaults"', false);
        $this->assertLessThan(
            strpos($pageContent, 'id="inventorySearchInput"'),
            strpos($pageContent, 'id="btnAddItems"')
        );

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
        $this->assertEquals('#facc15', $wand->type_dot_color);
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

        $goldDepositResponse = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/vault', [
            'operation' => 'deposit',
            'amount' => 5,
            'currency' => 'GP',
        ]);
        $goldDepositResponse->assertOk();
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 30050,
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


