<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ValiantShadeAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_succeeds_when_optional_user_columns_are_missing_and_logs_user_in(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('display_name');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_hash');
        });

        $response = $this->post(route('valiant-shade.auth-register-basic.post'), [
            'username' => 'NewPlayer',
            'email' => 'new-player@example.com',
            'password' => 'secret123',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('valiant-shade.pages-profile-user'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'username' => 'NewPlayer',
            'name' => '',
            'email' => 'new-player@example.com',
            'status' => 1,
        ]);
    }

    public function test_registration_keeps_display_name_blank_until_the_user_sets_it(): void
    {
        $this->post(route('valiant-shade.auth-register-basic.post'), [
            'username' => 'NewDisplayName',
            'email' => 'new-display-name@example.com',
            'password' => 'secret123',
            'terms' => '1',
        ])->assertRedirect(route('valiant-shade.pages-profile-user'));

        $user = User::where('email', 'new-display-name@example.com')->firstOrFail();
        $this->assertSame('', $user->getRawOriginal('name'));
        if (Schema::hasColumn('users', 'display_name')) {
            $this->assertSame('', $user->getRawOriginal('display_name'));
        }

        $this->get('/valiant-shade/pages/account-settings-account')
            ->assertOk()
            ->assertSee('name="displayName" value=""', false);

        $this->post('/valiant-shade/pages/account-settings-account', [
            'displayName' => 'Shadow Blade',
            'username' => 'NewDisplayName',
            'email' => 'new-display-name@example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Shadow Blade',
        ]);
        if (Schema::hasColumn('users', 'display_name')) {
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'display_name' => 'Shadow Blade',
            ]);
        }
    }

    public function test_registration_form_marks_username_and_email_as_required(): void
    {
        $this->get('/valiant-shade/auth/register-basic')
            ->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('autocomplete="username" required', false)
            ->assertSee('type="email"', false)
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="email" required', false)
            ->assertDontSee('name="displayName"', false);
    }

    public function test_authenticated_user_name_is_displayed_on_profile(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $response = $this->actingAs($user)->get('/valiant-shade/pages/profile-user');

        $response->assertStatus(200);
        $response->assertSee('Erdem');
        $response->assertDontSee('(Admin)');
        $response->assertDontSee('<h4>John Doe</h4>', false);
    }

    public function test_user_can_authenticate_via_login_post(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456', 'status' => 1]
        );

        $response = $this->post('/valiant-shade/auth/login-basic', [
            'email-username' => 'admin@valiantshade.com',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('valiant-shade.dashboard-analytics'));
        $this->assertAuthenticated();
    }

    public function test_user_can_authenticate_with_username(): void
    {
        User::factory()->create([
            'username' => 'guild-member',
            'email' => 'guild-member@example.com',
            'password' => 'secret123',
            'status' => 1,
        ]);

        $response = $this->post('/valiant-shade/auth/login-basic', [
            'email-username' => 'guild-member',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('valiant-shade.dashboard-analytics'));
        $this->assertAuthenticated();
    }

    public function test_active_member_can_open_the_valiant_shade_home_dashboard(): void
    {
        $user = User::factory()->create([
            'status' => 1,
            'role' => 'PLAYER',
        ]);

        $this->actingAs($user)
            ->get('/valiant-shade')
            ->assertOk();
    }

    public function test_login_from_beta_destination_returns_member_to_home_dashboard(): void
    {
        User::factory()->create([
            'email' => 'beta-member@example.com',
            'password' => 'secret123',
            'status' => 1,
            'role' => 'PLAYER',
        ]);

        $this->get('/valiant-shade')
            ->assertRedirect(route('valiant-shade.auth-login-basic'));

        $response = $this->post('/valiant-shade/auth/login-basic', [
            'email-username' => 'beta-member@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/valiant-shade');
        $this->assertAuthenticated();
        $this->get('/valiant-shade')->assertOk();
    }

    public function test_local_session_domain_matches_the_request_host(): void
    {
        $provider = new \App\Providers\AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'configureLocalSessionForGazetteSubdomain');
        $method->setAccessible(true);

        $originalEnvironment = $this->app->environment();
        $originalRequest = $this->app->make('request');
        $originalSessionDomain = config('session.domain');

        try {
            $this->app->detectEnvironment(static fn () => 'local');
            config(['session.domain' => null]);

            $this->app->instance('request', Request::create('http://127.0.0.1:8000'));
            $method->invoke($provider);
            $this->assertNull(config('session.domain'));

            $this->app->instance('request', Request::create('http://localhost:8000'));
            $method->invoke($provider);
            $this->assertSame('.localhost', config('session.domain'));
        } finally {
            $this->app->detectEnvironment(static fn () => $originalEnvironment);
            $this->app->instance('request', $originalRequest);
            config(['session.domain' => $originalSessionDomain]);
        }
    }

    public function test_pending_user_gets_approval_message_without_being_logged_in(): void
    {
        User::factory()->create([
            'email' => 'pending-member@example.com',
            'password' => 'secret123',
            'status' => 2,
        ]);

        $response = $this->from('/valiant-shade/auth/login-basic')->post('/valiant-shade/auth/login-basic', [
            'email-username' => 'pending-member@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/valiant-shade/auth/login-basic');
        $response->assertSessionHasErrors('email-username', __('Your account is awaiting approval.'));
        $this->assertGuest();
    }

    public function test_user_can_logout_safely(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $response = $this->actingAs($user)->post('/valiant-shade/logout');

        $response->assertRedirect(route('valiant-shade.auth-login-basic'));
        $this->assertGuest();
    }

    public function test_dark_mode_cookie_is_applied_to_html(): void
    {
        $response = $this->withUnencryptedCookies(['valiant-shade-mode' => 'dark'])->get('/valiant-shade');

        $response->assertStatus(200);
        $response->assertSee('data-bs-theme="dark"', false);
    }

    public function test_user_specific_theme_cookie_is_loaded(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $userThemes = json_encode([
            (string) $user->id => 'dark',
            'guest' => 'light',
        ]);

        $response = $this->actingAs($user)
            ->withUnencryptedCookies(['valiant-shade-user-themes' => $userThemes])
            ->get('/valiant-shade/pages/profile-user');

        $response->assertStatus(200);
        $response->assertSee('data-bs-theme="dark"', false);
    }

    public function test_guest_can_browse_pages_freely(): void
    {
        $response1 = $this->get('/valiant-shade');
        $response1->assertStatus(200);

        $response2 = $this->get('/valiant-shade/dashboard/crm');
        $response2->assertStatus(200);

        $response3 = $this->get('/valiant-shade/pages/xp-tracker');
        $response3->assertStatus(200);
    }

    public function test_guest_does_not_see_misafir_kullanici_and_sees_login_button(): void
    {
        $response = $this->get('/valiant-shade');

        $response->assertStatus(200);
        $response->assertDontSee('Misafir Kullanıcı');
        $response->assertSee(__('Sign In'));
        $response->assertSee(route('valiant-shade.auth-login-basic'));
    }

    public function test_authenticated_user_sees_their_name_and_not_login_button(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $response = $this->actingAs($user)->get('/valiant-shade');

        $response->assertStatus(200);
        $response->assertSee('Erdem');
        $response->assertDontSee('(Admin)');
        $response->assertDontSee('Misafir Kullanıcı');
        $response->assertDontSee('<i class="icon-base ti tabler-login me-1 icon-16px"></i>', false);
    }

    public function test_guest_mutating_actions_redirect_to_login(): void
    {
        $response = $this->post('/valiant-shade/logout');

        $response->assertRedirect(route('valiant-shade.auth-login-basic'));
    }

    public function test_guest_ajax_mutating_action_returns_401_with_redirect(): void
    {
        $response = $this->postJson('/valiant-shade/logout');

        $response->assertStatus(401);
        $response->assertJson([
            'message' => 'Please sign in to perform this action.',
            'redirect' => route('valiant-shade.auth-login-basic'),
        ]);
    }

    public function test_valiant_shade_logo_is_rendered_in_views(): void
    {
        $response = $this->get('/valiant-shade');

        $response->assertStatus(200);
        $response->assertSee('valiant-shade-logo-small.png');
    }

    public function test_user_profile_photo_url_defaults_to_user_png(): void
    {
        $user = new User();
        $this->assertStringContainsString('avatars/user.png', $user->profile_photo_url);
        $this->assertFileExists(public_path('static/valiant-shade/assets/img/avatars/user.png'));
    }

    public function test_valiant_shade_logo_is_stored_in_branding_directory_and_not_in_root(): void
    {
        $this->assertFileExists(public_path('static/valiant-shade/assets/img/branding/Valiant_Shade_Logo.jpg'));
        $this->assertFileDoesNotExist(base_path('Valiant_Shade_Logo.jpg'));
        $this->assertFileDoesNotExist(public_path('Valiant_Shade_Logo.jpg'));
    }

    public function test_profile_page_renders_user_png_instead_of_avatar_1(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        $response = $this->actingAs($user)->get('/valiant-shade/pages/profile-user');

        $response->assertStatus(200);
        $response->assertSee('avatars/user.png');
        $response->assertSee('src="' . $user->profile_photo_url . '" alt="user image"', false);
    }

    public function test_valiant_shade_and_demo_vuexy_images_are_completely_isolated(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        // Valiant Shade loads valiant-shade custom branding and reuses demo avatars from demo-vuexy
        $vsResponse = $this->actingAs($user)->get('/valiant-shade');
        $vsResponse->assertStatus(200);
        $vsResponse->assertSee('/static/valiant-shade/', false);

        // Demo Vuexy must only load demo-vuexy assets and zero valiant-shade assets
        $demoResponse = $this->actingAs($user)->get('/demo-vuexy');
        $demoResponse->assertStatus(200);
        $demoResponse->assertSee('/static/demo-vuexy/', false);
        $demoResponse->assertDontSee('/static/valiant-shade/', false);
    }

    public function test_uuid_is_visible_to_admins_only_in_account_profile_and_navbar(): void
    {
        $user = User::factory()->create([
            'name' => 'Erdem',
            'email' => 'member-uuid@example.com',
            'role' => 'PLAYER',
            'status' => 1,
        ]);

        if (empty($user->uuid)) {
            $user->uuid = (string) \Illuminate\Support\Str::uuid();
            $user->save();
        }

        $profileResponse = $this->actingAs($user)->get('/valiant-shade/pages/profile-user');
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('Erdem');
        $profileResponse->assertDontSee($user->uuid);
        $profileResponse->assertSee('ID: #' . $user->id);
        $profileResponse->assertSee('<span class="badge bg-label-primary font-monospace fs-7 d-inline-flex align-items-center">ID: #' . $user->id . '</span>', false);

        $dashboardResponse = $this->actingAs($user)->get('/valiant-shade');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertDontSee($user->uuid);

        foreach ([
            '/valiant-shade/pages/profile-teams',
            '/valiant-shade/pages/profile-projects',
            '/valiant-shade/pages/profile-connections',
        ] as $profilePath) {
            $this->actingAs($user)
                ->get($profilePath)
                ->assertOk()
                ->assertDontSee($user->uuid);
        }

        $accountResponse = $this->actingAs($user)->get('/valiant-shade/pages/account-settings-account');
        $accountResponse->assertStatus(200);
        $accountResponse->assertDontSee('Unique User UUID');
        $accountResponse->assertDontSee('Account Status');
        $accountResponse->assertDontSee('Guild / Organization');
        $accountResponse->assertDontSee($user->uuid);

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'uuid-admin@example.com',
            'role' => 'ADMIN',
            'status' => 1,
        ]);

        $adminProfileResponse = $this->actingAs($admin)->get('/valiant-shade/pages/profile-user');
        $adminProfileResponse->assertOk()->assertSee('UUID: ' . $admin->uuid);

        $adminDashboardResponse = $this->actingAs($admin)->get('/valiant-shade');
        $adminDashboardResponse->assertOk()->assertSee('UUID: ' . substr($admin->uuid, 0, 16) . '...');

        $adminAccountResponse = $this->actingAs($admin)->get('/valiant-shade/pages/account-settings-account');
        $adminAccountResponse->assertOk()->assertSee('Unique User UUID')->assertSee($admin->uuid);
    }

    public function test_account_settings_render_multiple_assigned_roles_as_read_only_badges(): void
    {
        $user = User::factory()->create([
            'role' => 'PLAYER',
            'status' => 1,
        ]);
        $user->syncRoles([
            Role::where('slug', 'PLAYER')->firstOrFail(),
            Role::where('slug', 'VETERAN')->firstOrFail(),
        ]);

        $this->actingAs($user)
            ->get('/valiant-shade/pages/account-settings-account')
            ->assertOk()
            ->assertSee('Adventurer')
            ->assertSee('Veteran Adventurer')
            ->assertSee('role="listitem"', false);
    }

    public function test_admin_can_destroy_user_character_by_id_and_uuid(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456', 'role' => 'Admin']
        );

        $targetUser = User::create([
            'name' => 'Test Target',
            'email' => 'target@valiantshade.com',
            'password' => 'secret123',
            'role' => 'User',
            'current_character' => 'Valerius (Barbar)',
        ]);

        $this->assertNotNull($targetUser->current_character);

        // Delete by numeric ID
        $response1 = $this->actingAs($admin)->deleteJson('/valiant-shade/app/user/' . $targetUser->id . '/character');
        $response1->assertStatus(200);
        $response1->assertJson(['success' => true]);
        $this->assertNull($targetUser->fresh()->current_character);

        // Set character again and delete by UUID
        $targetUser->current_character = 'Thorn (Paladin)';
        $targetUser->save();

        $response2 = $this->actingAs($admin)->deleteJson('/valiant-shade/app/user/' . $targetUser->uuid . '/character');
        $response2->assertStatus(200);
        $response2->assertJson(['success' => true]);
        $this->assertNull($targetUser->fresh()->current_character);
    }

    public function test_user_can_sync_and_clear_own_character(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        // Set character via sync
        $response = $this->actingAs($user)->postJson('/valiant-shade/app/user/sync-character', [
            'character' => 'Caspian Thunderblade',
        ]);
        $response->assertStatus(200);
        $this->assertSame('Caspian Thunderblade', $user->fresh()->current_character);

        // Clear character via sync with null
        $response2 = $this->actingAs($user)->postJson('/valiant-shade/app/user/sync-character', [
            'character' => null,
        ]);
        $response2->assertStatus(200);
        $this->assertNull($user->fresh()->current_character);
    }
}


