<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GuildVault;
use App\ValiantShade\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValiantShadeTest extends TestCase
{
    use RefreshDatabase;
    public function test_home_page_has_public_seo_and_favicon(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<title>'.config('seo.home.title').'</title>', false)
            ->assertSee('name="description"', false)
            ->assertSee(config('seo.home.description'), false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('rel="icon"', false)
            ->assertSee(url('/favicon.ico'), false)
            ->assertSee('application/ld+json', false);
    }

    public function test_home_page_beta_links_to_valiant_shade_panel(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('href="/valiant-shade"', false);
        $response->assertSee('vs-quad__cell--dimmed', false);
        $response->assertSee('BETA');
    }

    public function test_valiant_shade_dashboard_redirects_guests_to_login(): void
    {
        $response = $this->get('/valiant-shade');

        $response->assertRedirect(route('valiant-shade.auth-login-basic'));
    }

    public function test_dashboard_inventory_chart_converts_gp_to_sp_and_keeps_sold_items_in_history(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $inventoryService = app(InventoryService::class);
        $item = $inventoryService->createItem([
            'name' => 'Dashboard chart test item',
            'type' => 'Weapon',
            'quantity' => 2,
            'unit_price' => 100,
        ]);
        $soldItem = $inventoryService->createItem([
            'name' => 'Dashboard chart sold item',
            'type' => 'Potion',
            'quantity' => 1,
            'unit_price' => 50,
        ]);
        $inventoryService->sellItems([$soldItem->id]);

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/valiant-shade');

        $response->assertOk()
            ->assertSee('tabler-building-bank', false)
            ->assertSee('tabler-clipboard-list', false)
            ->assertDontSee('tabler-vault', false)
            ->assertDontSee('tabler-scroll', false)
            ->assertSee('2,000')
            ->assertSee('200.0 GP')
            ->assertSee('~110 GP');

        $this->assertSame(
            1,
            preg_match('/id="guildInventory30DayChart"[^>]*data-daily-sp="([^"]*)"/s', $response->getContent(), $matches)
        );
        $dailySp = json_decode(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(2500, $dailySp[array_key_last($dailySp)]);
    }

    public function test_dashboard_shows_current_inventory_value_and_inventory_vault_balance(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);
        $inventoryService = app(InventoryService::class);
        $inventoryService->updateVaultBalance(130000);
        GuildVault::create([
            'id' => 2,
            'name' => 'Ana Lonca Kasası',
            'balance_sp' => 358989.50,
        ]);
        $inventoryService->createItem([
            'name' => 'Active dashboard item',
            'type' => 'Weapon',
            'quantity' => 1,
            'unit_price' => 7000,
        ]);
        $soldItem = $inventoryService->createItem([
            'name' => 'Sold dashboard item',
            'type' => 'Weapon',
            'quantity' => 1,
            'unit_price' => 3000,
        ]);
        $soldItem->delete();

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/valiant-shade');

        $response->assertOk()
            ->assertSee('130,000.00')
            ->assertSee('70,000')
            ->assertSee('7,000.0 GP')
            ->assertSee('100,000 SP')
            ->assertSee('Current Inventory Value');
    }

    public function test_valiant_shade_login_renders_successfully(): void
    {
        $response = $this->get('/valiant-shade/auth/login-basic');

        $response->assertStatus(200);
        $response->assertSee('Valiant Shade');
        $response->assertSee(__('Please sign-in to your account and start the adventure'));
    }

    public function test_demo_vuexy_remains_untouched(): void
    {
        $response = $this->get('/demo-vuexy');

        $response->assertStatus(200);
        $response->assertSee('Vuexy');
    }

    public function test_demo_vuexy_renders_for_authenticated_user(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $response = $this->actingAs($user)->get('/demo-vuexy');

        $response->assertStatus(200);
        $response->assertSee('Vuexy');
    }

    public function test_admin_renders_for_guest_and_authenticated_user(): void
    {
        $guestResponse = $this->get('/admin');
        $guestResponse->assertStatus(200);

        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $authResponse = $this->actingAs($user)->get('/admin');
        $authResponse->assertStatus(200);
    }

    public function test_all_active_valiant_shade_routes_render_successfully(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $routes = [
            '/valiant-shade',
            '/valiant-shade/dashboard/analytics',
            '/valiant-shade/dashboard/crm',
            '/valiant-shade/pages/profile-user',
            '/valiant-shade/pages/profile-teams',
            '/valiant-shade/pages/profile-projects',
            '/valiant-shade/pages/profile-connections',
            '/valiant-shade/pages/account-settings-account',
            '/valiant-shade/pages/inventory',
            '/valiant-shade/pages/xp-tracker',
            '/valiant-shade/pages/adventurer-info',
            '/valiant-shade/pages/quest-board',
            '/valiant-shade/pages/quest-reports',
            '/valiant-shade/auth/login-basic',
            '/valiant-shade/auth/register-basic',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->get($route);
            $this->assertSame(200, $response->getStatusCode(), "Route {$route} failed with status {$response->getStatusCode()}");
        }
    }

    public function test_removed_pages_not_in_valiant_shade_routes_or_menus(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        // Tables and Misc Error were removed from Valiant Shade
        $removedRoutes = [
            '/valiant-shade/tables/basic',
            '/valiant-shade/pages/misc-error',
        ];

        foreach ($removedRoutes as $route) {
            $response = $this->actingAs($user)->get($route);
            $this->assertSame(404, $response->getStatusCode(), "Route {$route} should return 404 in Valiant Shade");
        }

        // Verify removed from Valiant Shade menus
        $verticalMenu = file_get_contents(resource_path('menu/valiant-shade/verticalMenu.json'));
        $horizontalMenu = file_get_contents(resource_path('menu/valiant-shade/horizontalMenu.json'));

        $this->assertStringNotContainsString('tables-basic', $verticalMenu);
        $this->assertStringNotContainsString('tables/basic', $verticalMenu);
        $this->assertStringNotContainsString('pages-misc-error', $verticalMenu);
        $this->assertStringNotContainsString('misc-error', $verticalMenu);

        $this->assertStringNotContainsString('tables-basic', $horizontalMenu);
        $this->assertStringNotContainsString('tables/basic', $horizontalMenu);
        $this->assertStringNotContainsString('pages-misc-error', $horizontalMenu);
        $this->assertStringNotContainsString('misc-error', $horizontalMenu);
    }

    public function test_default_locale_is_english(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $response = $this->actingAs($user)->get('/valiant-shade');
        $response->assertStatus(200);
        $response->assertSee('Dashboards');
        $response->assertSee('Guild Inventory');
        $response->assertSee('Player XP Tracker');
    }

    public function test_locale_switching_to_turkish_and_back_to_english(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        // Switch to Turkish
        $switchTr = $this->actingAs($user)->get('/valiant-shade/lang/tr');
        $switchTr->assertSessionHas('locale', 'tr');

        // Request with session set to 'tr'
        $responseTr = $this->actingAs($user)->withSession(['locale' => 'tr'])->get('/valiant-shade');
        $responseTr->assertStatus(200);
        $responseTr->assertSee('Kontrol Panelleri');
        $responseTr->assertSee('Lonca Envanteri');
        $responseTr->assertSee('Oyuncu XP Takibi');

        // Switch back to English
        $switchEn = $this->actingAs($user)->get('/valiant-shade/lang/en');
        $switchEn->assertSessionHas('locale', 'en');

        // Request with session set to 'en'
        $responseEn = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/valiant-shade');
        $responseEn->assertStatus(200);
        $responseEn->assertSee('Dashboards');
        $responseEn->assertSee('Guild Inventory');
    }

    public function test_character_sheet_i18n_and_craft_slots(): void
    {
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        // English Character Sheet
        $resEn = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/valiant-shade/character/sheet');
        $resEn->assertStatus(200);
        $resEn->assertSee('Character Name');
        $resEn->assertSee('Ability Scores');
        $resEn->assertSee('Saving Throws');
        $resEn->assertSee('Skills');
        $resEn->assertSee('Weapons');
        $resEn->assertSee('craft1');
        $resEn->assertSee('craft2');
        $resEn->assertDontSee('craft3');

        // Turkish Character Sheet
        $resTr = $this->actingAs($user)->withSession(['locale' => 'tr'])->get('/valiant-shade/character/sheet');
        $resTr->assertStatus(200);
        $resTr->assertSee('Karakter Adı');
        $resTr->assertSee('Nitelikler');
        $resTr->assertSee('Kurtarma Atışları');
        $resTr->assertSee('Beceriler');
        $resTr->assertSee('Silahlar');
    }
}

