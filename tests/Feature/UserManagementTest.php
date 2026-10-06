<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name'      => 'Test Admin',
            'email'     => 'admin@test.local',
            'password'  => Hash::make('password123'),
            'role_id'   => Role::where('name', 'admin')->first()->id,
            'is_active' => true,
        ]);
    }

    private function createStudent(): User
    {
        return User::create([
            'name'      => 'Test Student',
            'email'     => 'student@test.local',
            'password'  => Hash::make('password123'),
            'role_id'   => Role::where('name', 'student')->first()->id,
            'is_active' => true,
        ]);
    }

    private function createCounselor(): User
    {
        return User::create([
            'name'      => 'Test Counselor',
            'email'     => 'counselor@test.local',
            'password'  => Hash::make('password123'),
            'role_id'   => Role::where('name', 'counselor')->first()->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_student_cannot_access_user_management(): void
    {
        $this->actingAs($this->createStudent());
        $this->get('/admin/users')->assertStatus(403);
    }

    public function test_counselor_cannot_access_user_management(): void
    {
        $this->actingAs($this->createCounselor());
        $this->get('/admin/users')->assertStatus(403);
    }

    public function test_admin_can_access_user_management(): void
    {
        $this->actingAs($this->createAdmin());
        $this->get('/admin/users')->assertOk();
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);
        $this->patch("/admin/users/{$admin->id}/deactivate")
             ->assertSessionHasErrors('error');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_cannot_deactivate_last_active_admin(): void
    {
        $admin = $this->createAdmin();
        $this->actingAs($admin);

        $otherAdmin = User::create([
            'name'      => 'Other Admin',
            'email'     => 'other@test.local',
            'password'  => Hash::make('password123'),
            'role_id'   => Role::where('name', 'admin')->first()->id,
            'is_active' => false,
        ]);

        $this->patch("/admin/users/{$admin->id}/deactivate")
             ->assertSessionHasErrors('error');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->createStudent();
        $user->update(['is_active' => false]);

        $this->post('/login', [
            'email'    => 'student@test.local',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}