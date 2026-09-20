<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValiantShadeAuthTest extends TestCase
{
    use RefreshDatabase;
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
            ['name' => 'Erdem', 'password' => '123456']
        );

        $response = $this->post('/valiant-shade/auth/login-basic', [
            'email-username' => 'admin@valiantshade.com',
            'password' => '123456',
        ]);

        $response->assertRedirect(route('valiant-shade.pages-profile-user'));
        $this->assertAuthenticated();
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

    public function test_unique_user_id_is_rendered_under_user_name_in_profile_and_navbar(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@valiantshade.com'],
            ['name' => 'Erdem', 'password' => '123456']
        );

        if (empty($user->uuid)) {
            $user->uuid = (string) \Illuminate\Support\Str::uuid();
            $user->save();
        }

        // Profile Page: user UUID badge and ID badge are rendered under the name
        $profileResponse = $this->actingAs($user)->get('/valiant-shade/pages/profile-user');
        $profileResponse->assertStatus(200);
        $profileResponse->assertSee('Erdem');
        $profileResponse->assertDontSee('(Admin)');
        $profileResponse->assertSee('UUID: ' . $user->uuid);
        $profileResponse->assertSee('ID: #' . $user->id);
        $profileResponse->assertSee('<span class="badge bg-label-primary font-monospace fs-7 d-inline-flex align-items-center">UUID: ' . $user->uuid . '</span>', false);
        $profileResponse->assertSee('<span class="badge bg-label-primary font-monospace fs-7 d-inline-flex align-items-center">ID: #' . $user->id . '</span>', false);
        $profileResponse->assertDontSee('tabler-fingerprint');
        $profileResponse->assertDontSee('bg-label-secondary font-monospace fs-7');

        // Dashboard Navbar: user UUID is rendered in the user dropdown without fingerprint
        $dashboardResponse = $this->actingAs($user)->get('/valiant-shade');
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('UUID:');
        $dashboardResponse->assertSee('Erdem');
        $dashboardResponse->assertDontSee('(Admin)');
        $dashboardResponse->assertDontSee('tabler-fingerprint');
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


