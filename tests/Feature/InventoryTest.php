<?php

namespace Tests\Feature;

use App\Models\Character;
use App\Models\CharacterInfo;
use App\Models\CharacterInventoryAssignment;
use App\Models\CharacterProfile;
use App\Models\GuildVault;
use App\Models\InventoryItem;
use App\Models\InventorySaleLog;
use App\Models\PlayerXp;
use App\Models\User;
use App\ValiantShade\Services\InventoryService;
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
            'market_value' => 50000, // 50% of 100,000
            'buy_price' => 25000,    // 50,000 / 2
            'sell_price' => 27500,   // 55% / unit
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
            ->assertSee('Piyasa Satış (%55 / unit');

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
            'buy_price' => 500,
            'sell_price' => 550,
        ]);
        $this->assertDatabaseHas('inventory_items', [
            'name' => 'Bulk Wand',
            'total_price' => 1000,
            'buy_price' => 250,
            'sell_price' => 275,
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

        $inventoryService = app(InventoryService::class);
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
            ->assertJsonPath('proceedsSp', 1430);
        $this->assertSoftDeleted('inventory_items', ['id' => $firstItem->id]);
        $this->assertSoftDeleted('inventory_items', ['id' => $secondItem->id]);
        $this->assertDatabaseHas('guild_vaults', ['id' => 1, 'balance_sp' => 31430]);
    }

    public function test_market_sale_is_logged_and_can_be_reversed_once(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $item = app(InventoryService::class)->createItem([
            'name' => 'Sale reversal sword', 'type' => 'Weapon', 'quantity' => 2, 'unit_price' => 100,
            'uses_remaining' => 5,
        ]);
        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/sell', ['ids' => [$item->id]]);
        $response->assertOk()->assertJsonPath('proceedsSp', 1100);
        $logId = $response->json('saleLogId');
        $this->assertDatabaseHas('inventory_sale_logs', ['id' => $logId, 'destination' => 'market', 'sold_by' => $admin->id]);
        $this->actingAs($admin)->get('/valiant-shade/pages/inventory')
            ->assertOk()->assertSee('Sale reversal sword')->assertSee('sell-revert/'.$logId, false);

        $this->actingAs($admin)->postJson("/valiant-shade/pages/inventory/sell-revert/{$logId}")
            ->assertOk()->assertJsonPath('restoredCount', 1);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'deleted_at' => null, 'uses_remaining' => 5]);
        $this->assertDatabaseHas('guild_vaults', ['id' => 1, 'balance_sp' => 30000]);
        $this->actingAs($admin)->postJson("/valiant-shade/pages/inventory/sell-revert/{$logId}")
            ->assertUnprocessable();
        $this->assertDatabaseHas('financial_ledger', ['transaction_type' => 'ITEM_SALE_REVERT', 'amount_sp' => -1100]);
    }

    public function test_character_sale_and_reversal_preserve_later_balance_changes(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $owner = User::factory()->create();
        $character = Character::create(['user_id' => $owner->id, 'name' => 'Sale Hero', 'status' => 'ACTIVE', 'allowance_sp' => 2000]);
        CharacterProfile::create(['user_id' => $owner->id, 'character_name' => 'Sale Hero', 'class_name' => 'Fighter', 'rank' => 'Üye']);
        $info = CharacterInfo::create(['character_id' => $character->id, 'pocket_money' => ['gp' => 12, 'sp' => 3]]);
        PlayerXp::create(['user_id' => $owner->id, 'character_name' => 'Sale Hero', 'allowance_sp' => 2000]);
        $item = app(InventoryService::class)->createItem(['name' => 'Purchased Blade', 'type' => 'Weapon', 'quantity' => 2, 'unit_price' => 100]);
        $item->update(['sell_price' => 50]);

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/sell', ['ids' => [$item->id], 'character_id' => $character->id]);
        $response->assertOk()->assertJsonPath('proceedsSp', 1100);
        $logId = $response->json('saleLogId');
        $this->assertDatabaseHas('inventory_sale_logs', ['id' => $logId, 'destination' => 'character', 'buyer_character_id' => $character->id]);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'character_id' => $character->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'allowance_sp' => 900]);
        $this->assertDatabaseHas('guild_vaults', ['id' => 1, 'balance_sp' => 31100]);
        $this->assertDatabaseHas('financial_ledger', ['character_id' => $character->id, 'transaction_type' => 'ITEM_SALE', 'amount_sp' => -1100]);
        $this->assertSame(['gp' => 12, 'sp' => 3], $info->fresh()->pocket_money);
        $this->actingAs($owner)->get('/valiant-shade/character/sheet?name=Sale%20Hero')->assertOk()->assertSee('Purchased Blade');

        Character::whereKey($character->id)->update(['allowance_sp' => 1100]);
        PlayerXp::where('character_name', 'Sale Hero')->update(['allowance_sp' => 1100]);
        GuildVault::whereKey(1)->update(['balance_sp' => 30600]);
        $this->actingAs($admin)->postJson("/valiant-shade/pages/inventory/sell-revert/{$logId}")->assertOk();
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'allowance_sp' => 2200]);
        $this->assertDatabaseHas('player_xps', ['character_name' => 'Sale Hero', 'allowance_sp' => 2200]);
        $this->assertDatabaseHas('guild_vaults', ['id' => 1, 'balance_sp' => 29500]);
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'character_id' => null]);
        $this->actingAs($owner)->get('/valiant-shade/character/sheet?name=Sale%20Hero')->assertDontSee('Purchased Blade');
    }

    public function test_sale_reversal_rejects_changed_items_and_insufficient_vault(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $owner = User::factory()->create();
        $character = Character::create(['user_id' => $owner->id, 'name' => 'Buyer', 'status' => 'ACTIVE', 'allowance_sp' => 1000]);
        $item = app(InventoryService::class)->createItem(['name' => 'Changed item', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 100]);
        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/sell', ['ids' => [$item->id], 'character_id' => $character->id]);
        $logId = $response->json('saleLogId');
        $response->assertOk();
        $item->refresh()->update(['quantity' => 2]);
        $this->actingAs($admin)->postJson("/valiant-shade/pages/inventory/sell-revert/{$logId}")->assertUnprocessable();
        $this->assertDatabaseHas('inventory_sale_logs', ['id' => $logId, 'reverted_at' => null]);
        $this->assertDatabaseHas('characters', ['id' => $character->id, 'allowance_sp' => 450]);
        $item->update(['quantity' => 1]);
        GuildVault::whereKey(1)->update(['balance_sp' => 0]);
        $this->actingAs($admin)->postJson("/valiant-shade/pages/inventory/sell-revert/{$logId}")->assertUnprocessable();
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'character_id' => $character->id]);
    }

    public function test_character_sale_requires_funds_and_sale_history_is_paginated(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $buyer = Character::create(['user_id' => $admin->id, 'name' => 'Poor buyer', 'status' => 'ACTIVE', 'allowance_sp' => 5]);
        $item = app(InventoryService::class)->createItem(['name' => 'Expensive sale', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 100]);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/sell', ['ids' => [$item->id], 'character_id' => $buyer->id])
            ->assertUnprocessable();
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'character_id' => null, 'deleted_at' => null]);
        $this->assertDatabaseCount('inventory_sale_logs', 0);

        for ($i = 1; $i <= 21; $i++) {
            InventorySaleLog::create(['sold_by' => $admin->id, 'proceeds_sp' => 1, 'items_snapshot' => [['name' => "Historic sale {$i}", 'quantity' => 1]]]);
        }
        $this->actingAs($admin)->get('/valiant-shade/pages/inventory?sales_page=2')
            ->assertOk()->assertSee('Historic sale 1')->assertDontSee('Historic sale 21');
    }

    public function test_reversal_rejects_items_used_after_sale_and_unauthorized_requests(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $owner = User::factory()->create(['role' => 'User']);
        $buyer = Character::create(['user_id' => $owner->id, 'name' => 'Item user', 'status' => 'ACTIVE', 'allowance_sp' => 1000]);
        $item = app(InventoryService::class)->createItem(['name' => 'Used item', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 100]);
        $this->actingAs($owner)->postJson('/valiant-shade/pages/inventory/sell', ['ids' => [$item->id], 'character_id' => $buyer->id])->assertForbidden();
        $logId = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/sell', ['ids' => [$item->id], 'character_id' => $buyer->id])
            ->assertOk()->json('saleLogId');
        $this->actingAs($owner)->postJson("/valiant-shade/pages/inventory/sell-revert/{$logId}")->assertForbidden();
        CharacterInventoryAssignment::create([
            'character_id' => $buyer->id, 'inventory_item_id' => $item->id,
            'custody_status' => CharacterInventoryAssignment::STATUS_CONSUMED,
            'assigned_at' => now(), 'returned_at' => now(),
        ]);
        $this->actingAs($admin)->postJson("/valiant-shade/pages/inventory/sell-revert/{$logId}")->assertUnprocessable();
        $this->assertDatabaseHas('inventory_sale_logs', ['id' => $logId, 'reverted_at' => null]);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/reset-defaults')->assertUnprocessable();
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'character_id' => $buyer->id]);
    }

    public function test_old_market_sale_snapshot_can_be_reversed(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $item = app(InventoryService::class)->createItem(['name' => 'Legacy item', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 100]);
        $log = InventorySaleLog::create([
            'sold_by' => $admin->id,
            'proceeds_sp' => 550,
            'items_snapshot' => [$item->only(['id', 'name', 'quality', 'type', 'quantity', 'unit_price', 'total_price', 'market_value', 'buy_price', 'sell_price', 'notes'])],
        ]);
        $item->delete();
        $this->actingAs($admin)->postJson("/valiant-shade/pages/inventory/sell-revert/{$log->id}")->assertOk();
        $this->assertDatabaseHas('inventory_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_character_sheet_does_not_mix_ambiguous_characters_in_inventory(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $owner = User::factory()->create();
        $buyer = Character::create(['user_id' => $owner->id, 'name' => 'Duplicate buyer', 'status' => 'ACTIVE', 'allowance_sp' => 1000]);
        CharacterProfile::create(['user_id' => $owner->id, 'character_name' => 'Duplicate buyer', 'class_name' => 'Fighter', 'rank' => 'Üye']);
        $item = app(InventoryService::class)->createItem(['name' => 'Ambiguous purchase', 'type' => 'Gear', 'quantity' => 1, 'unit_price' => 100]);
        $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/sell', ['ids' => [$item->id], 'character_id' => $buyer->id])->assertOk();
        Character::create(['user_id' => $owner->id, 'name' => 'Duplicate buyer', 'status' => 'ACTIVE']);
        $this->actingAs($owner)->get('/valiant-shade/character/sheet?name=Duplicate%20buyer')
            ->assertOk()->assertSee('Karakter eşleşmesi belirsiz')->assertDontSee('Ambiguous purchase');
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
            'market_value' => 15000,
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
            ->assertSee('action="'.route('valiant-shade.pages-inventory.store').'"', false)
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
            $response->assertSee('data-type="'.$type.'"', false);
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
            'market_value' => 375,
            'buy_price' => 375,
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
            'market_value' => 187.5,
            'buy_price' => 187.5,
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
            'market_value' => 200,
            'buy_price' => 100,
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
            'market_value' => 7.5,
            'buy_price' => 7.5,
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
            'market_value' => 29250,
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
            'market_value' => 5625,
            'buy_price' => 5625,
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
            'market_value' => 2250,
            'buy_price' => 2250,
        ]);
    }

    public function test_inventory_page_shows_cash_action_buttons_and_no_manage_button(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $response = $this->actingAs($admin)->get('/valiant-shade/pages/inventory');

        $response->assertStatus(200);
        $response->assertSee('data-vault-tab="deposit"', false);
        $response->assertSee('data-vault-tab="withdraw"', false);
        $response->assertSee('data-vault-tab="transfer"', false);
        $response->assertDontSee('> Manage</button>', false);
        $response->assertSee('Guild Vault & Cash Management');
    }

    public function test_admin_can_transfer_cash_from_vault_to_character(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $player = User::factory()->create(['role' => 'User']);
        $character = Character::create([
            'user_id' => $player->id,
            'name' => 'Valiant Warrior',
            'status' => 'ACTIVE',
            'current_level' => 3,
            'allowance_sp' => 200.00,
        ]);
        CharacterInfo::create([
            'character_id' => $character->id,
            'pocket_money' => ['pp' => 0, 'gp' => 20, 'sp' => 200, 'cp' => 0],
        ]);
        PlayerXp::create([
            'user_id' => $player->id,
            'character_name' => 'Valiant Warrior',
            'allowance_sp' => 200,
        ]);

        $response = $this->actingAs($admin)->post('/valiant-shade/pages/inventory/vault', [
            'operation' => 'transfer_to_character',
            'character_id' => $character->id,
            'amount' => 500,
            'currency' => 'SP',
            'notes' => 'Hero adventuring stipend',
        ]);

        $response->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 29500,
        ]);
        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'allowance_sp' => 700,
        ]);
        $this->assertDatabaseHas('player_xps', [
            'character_name' => 'Valiant Warrior',
            'allowance_sp' => 700,
        ]);
        $this->assertDatabaseHas('financial_ledger', [
            'character_id' => $character->id,
            'transaction_type' => 'WITHDRAWAL',
            'amount_sp' => -500,
        ]);
        $this->assertDatabaseHas('financial_ledger', [
            'character_id' => $character->id,
            'transaction_type' => 'DEPOSIT',
            'amount_sp' => 500,
        ]);
    }

    public function test_admin_can_transfer_cash_from_character_to_vault(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $player = User::factory()->create(['role' => 'User']);
        $character = Character::create([
            'user_id' => $player->id,
            'name' => 'Generous Mage',
            'status' => 'ACTIVE',
            'current_level' => 4,
            'allowance_sp' => 1000.00,
        ]);

        // Transfer 20 GP (= 200 SP) from character to vault
        $response = $this->actingAs($admin)->post('/valiant-shade/pages/inventory/vault', [
            'operation' => 'transfer_from_character',
            'character_id' => $character->id,
            'amount' => 20,
            'currency' => 'GP',
            'notes' => 'Tavern expansion contribution',
        ]);

        $response->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 30200,
        ]);
        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'allowance_sp' => 800,
        ]);
        $this->assertDatabaseHas('financial_ledger', [
            'character_id' => $character->id,
            'transaction_type' => 'WITHDRAWAL',
            'amount_sp' => -200,
        ]);
        $this->assertDatabaseHas('financial_ledger', [
            'character_id' => $character->id,
            'transaction_type' => 'DEPOSIT',
            'amount_sp' => 200,
        ]);
    }

    public function test_transfer_from_character_fails_when_character_has_insufficient_funds(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $player = User::factory()->create(['role' => 'User']);
        $character = Character::create([
            'user_id' => $player->id,
            'name' => 'Poor Rogue',
            'status' => 'ACTIVE',
            'current_level' => 1,
            'allowance_sp' => 50.00,
        ]);

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/vault', [
            'operation' => 'transfer_from_character',
            'character_id' => $character->id,
            'amount' => 100,
            'currency' => 'SP',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
            'allowance_sp' => 50.00,
        ]);
    }

    public function test_transfer_to_character_fails_when_vault_has_insufficient_funds(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin)->get('/valiant-shade/pages/inventory');
        $player = User::factory()->create(['role' => 'User']);
        $character = Character::create([
            'user_id' => $player->id,
            'name' => 'Greedy Paladin',
            'status' => 'ACTIVE',
            'current_level' => 2,
            'allowance_sp' => 0.00,
        ]);

        $response = $this->actingAs($admin)->postJson('/valiant-shade/pages/inventory/vault', [
            'operation' => 'transfer_to_character',
            'character_id' => $character->id,
            'amount' => 50000,
            'currency' => 'SP',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 30000,
        ]);
    }

    public function test_admin_can_distribute_cash_equally_to_characters(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $player1 = User::factory()->create(['role' => 'User']);
        $player2 = User::factory()->create(['role' => 'User']);
        $char1 = Character::create(['user_id' => $player1->id, 'name' => 'Hero One', 'status' => 'ACTIVE', 'allowance_sp' => 0]);
        $char2 = Character::create(['user_id' => $player2->id, 'name' => 'Hero Two', 'status' => 'ACTIVE', 'allowance_sp' => 0]);

        $response = $this->actingAs($admin)->post('/valiant-shade/pages/inventory/vault', [
            'operation' => 'distribute_characters',
            'character_ids' => [$char1->id, $char2->id],
            'amount' => 1000,
            'currency' => 'SP',
            'notes' => 'Expedition bounty equal split',
        ]);

        $response->assertRedirect('/valiant-shade/pages/inventory');
        $this->assertDatabaseHas('guild_vaults', [
            'id' => 1,
            'balance_sp' => 29000,
        ]);
        $this->assertDatabaseHas('characters', [
            'id' => $char1->id,
            'allowance_sp' => 500,
        ]);
        $this->assertDatabaseHas('characters', [
            'id' => $char2->id,
            'allowance_sp' => 500,
        ]);
    }

    public function test_non_admin_cannot_perform_character_transfers(): void
    {
        $user = User::factory()->create(['role' => 'User']);
        $char = Character::create(['user_id' => $user->id, 'name' => 'Target Hero', 'status' => 'ACTIVE', 'allowance_sp' => 100]);

        $response = $this->actingAs($user)->postJson('/valiant-shade/pages/inventory/vault', [
            'operation' => 'transfer_to_character',
            'character_id' => $char->id,
            'amount' => 100,
            'currency' => 'SP',
        ]);

        $response->assertStatus(403);
    }

    public function test_inventory_page_displays_character_allowances_table_with_metrics_and_actions(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $player = User::factory()->create(['name' => 'Sir Player']);
        $character = Character::create([
            'user_id' => $player->id,
            'name' => 'Galahad the Bold',
            'status' => 'ACTIVE',
            'current_level' => 7,
            'allowance_sp' => 1250,
        ]);

        $response = $this->actingAs($admin)->get('/valiant-shade/pages/inventory');

        $response->assertOk()
            ->assertSee('id="characterAllowancesCard"', false)
            ->assertSee('id="tableAllowancesList"', false)
            ->assertSee('id="searchAllowanceTable"', false)
            ->assertSee('Galahad the Bold')
            ->assertSee('Sir Player')
            ->assertSee('1,250.00')
            ->assertSee('data-char-id="'.$character->id.'"', false);
    }
}
