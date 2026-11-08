<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginPasswordToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    public function test_login_page_shows_password_hidden_by_default(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('type="password" id="password"', false);
    }

    public function test_login_page_has_show_hide_toggle_button(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-toggle-password="#password"', false)
            ->assertSee('type="button" class="password-toggle"', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('aria-pressed="false"', false);
    }

    public function test_toggle_button_does_not_submit_the_form(): void
    {
        $html = $this->get('/login')->getContent();

        $this->assertStringContainsString('type="button" class="password-toggle"', $html);
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*class="password-toggle"[^>]*type="submit"/',
            $html
        );
    }

    public function test_login_page_links_to_registration(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee(route('register'), false)
            ->assertSee('Create a student account', false);
    }

    public function test_register_page_has_toggles_for_both_password_fields(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('data-toggle-password="#password"', false)
            ->assertSee('data-toggle-password="#password_confirmation"', false);
    }

    public function test_register_page_password_fields_default_to_hidden(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('type="password" id="password"', false)
            ->assertSee('type="password" id="password_confirmation"', false);
    }

    public function test_existing_login_functionality_remains_intact(): void
    {
        $user = User::create([
            'name' => 'Login Test',
            'email' => 'logintest@test.local',
            'password' => 'secret-password',
            'role_id' => Role::where('name', Role::ADMIN)->first()->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'logintest@test.local',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_still_rejected(): void
    {
        User::create([
            'name' => 'Login Test',
            'email' => 'logintest2@test.local',
            'password' => 'secret-password',
            'role_id' => Role::where('name', Role::ADMIN)->first()->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'logintest2@test.local',
                'password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
