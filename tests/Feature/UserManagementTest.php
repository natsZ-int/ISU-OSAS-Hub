<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        // Make the default test admin the seeded System Administrator.
        config(['app.system_admin_email' => 'admin@test.local']);
    }

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('name', 'admin')->first()->id,
            'is_active' => true,
        ]);
    }

    private function createStudent(): User
    {
        return User::create([
            'name' => 'Test Student',
            'email' => 'student@test.local',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('name', 'student')->first()->id,
            'is_active' => true,
        ]);
    }

    private function createCounselor(): User
    {
        return User::create([
            'name' => 'Test Counselor',
            'email' => 'counselor@test.local',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('name', 'counselor')->first()->id,
            'is_active' => true,
        ]);
    }

    private function createSecondaryAdmin(): User
    {
        return User::create([
            'name' => 'Other Admin',
            'email' => 'other-admin@test.local',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('name', 'admin')->first()->id,
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
            'name' => 'Other Admin',
            'email' => 'other@test.local',
            'password' => Hash::make('password123'),
            'role_id' => Role::where('name', 'admin')->first()->id,
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
            'email' => 'student@test.local',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_update_student_enrollment_status(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $student->studentProfile()->create([
            'student_number' => '2024-0001',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'year_level' => 1,
        ]);

        $this->actingAs($admin);
        $this->put("/admin/users/{$student->id}", [
            'name' => $student->name,
            'email' => $student->email,
            'role_id' => $student->role_id,
            'student_number' => '2024-0001',
            'enrollment_status' => 'enrolled',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('enrolled', $student->fresh()->studentProfile->enrollment_status);
    }

    public function test_admin_sees_enrollment_status_on_users_index(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $student->studentProfile()->create([
            'student_number' => '2024-0002',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'year_level' => 1,
            'enrollment_status' => 'enrolled',
        ]);

        $this->actingAs($admin);
        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('Enrolled');
    }

    public function test_update_rejects_invalid_enrollment_status(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $student->studentProfile()->create([
            'student_number' => '2024-0003',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'year_level' => 1,
        ]);

        $this->actingAs($admin);
        $this->put("/admin/users/{$student->id}", [
            'name' => $student->name,
            'email' => $student->email,
            'role_id' => $student->role_id,
            'student_number' => '2024-0003',
            'enrollment_status' => 'graduated',
        ])->assertSessionHasErrors('enrollment_status');
    }

    public function test_admin_receives_typeahead_user_suggestions(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $student->studentProfile()->create([
            'student_number' => '2024-0009',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'year_level' => 1,
        ]);

        $this->actingAs($admin);

        $this->getJson('/admin/users/suggestions?q=stu')
            ->assertOk()
            ->assertJsonPath('query', 'stu')
            ->assertJsonFragment(['name' => 'Test Student', 'match' => 'name']);

        $this->getJson('/admin/users/suggestions?q=2024-0009')
            ->assertOk()
            ->assertJsonFragment([
                'student_number' => '2024-0009',
                'match' => 'student number',
                'search' => '2024-0009',
            ]);
    }

    public function test_typeahead_user_suggestions_are_admin_only(): void
    {
        $student = $this->createStudent();

        $this->actingAs($student)
            ->getJson('/admin/users/suggestions?q=test')
            ->assertForbidden();
    }

    public function test_only_the_system_admin_can_change_user_roles(): void
    {
        $systemAdmin = $this->createAdmin();
        $otherAdmin = $this->createSecondaryAdmin();
        $student = $this->createStudent();
        $counselorRole = Role::where('name', Role::COUNSELOR)->firstOrFail();

        $payload = [
            'name' => $student->name,
            'email' => $student->email,
            'role_id' => $counselorRole->id,
        ];

        $this->actingAs($otherAdmin)
            ->put("/admin/users/{$student->id}", $payload)
            ->assertSessionHasErrors('role_id');

        $this->assertSame(Role::STUDENT, $student->fresh()->role->name);

        $this->actingAs($systemAdmin)
            ->put("/admin/users/{$student->id}", $payload)
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame(Role::COUNSELOR, $student->fresh()->role->name);
    }

    public function test_other_admins_can_update_users_without_changing_roles(): void
    {
        $otherAdmin = $this->createSecondaryAdmin();
        $student = $this->createStudent();

        $this->actingAs($otherAdmin)
            ->put("/admin/users/{$student->id}", [
                'name' => 'Renamed Student',
                'email' => $student->email,
            ])
            ->assertRedirect(route('admin.users.index'));

        $student->refresh();

        $this->assertSame('Renamed Student', $student->name);
        $this->assertSame(Role::STUDENT, $student->role->name);
    }

    public function test_other_admins_see_role_as_read_only(): void
    {
        $otherAdmin = $this->createSecondaryAdmin();
        $student = $this->createStudent();

        $this->actingAs($otherAdmin)
            ->get("/admin/users/{$student->id}/edit")
            ->assertOk()
            ->assertSee('Only the System Administrator can change user roles.')
            ->assertDontSee('name="role_id"', false);
    }

    public function test_system_admin_email_is_locked(): void
    {
        $systemAdmin = $this->createAdmin();

        $this->actingAs($systemAdmin)
            ->put("/admin/users/{$systemAdmin->id}", [
                'name' => $systemAdmin->name,
                'email' => 'renamed-admin@test.local',
            ])
            ->assertSessionHasErrors('email');

        $this->assertSame('admin@test.local', $systemAdmin->fresh()->email);
    }

    public function test_only_the_system_admin_can_create_administrator_accounts(): void
    {        $otherAdmin = $this->createSecondaryAdmin();
        $adminRole = Role::where('name', Role::ADMIN)->firstOrFail();

        $this->actingAs($otherAdmin)
            ->post('/admin/users', [
                'name' => 'Another Admin',
                'email' => 'another-admin@test.local',
                'role_id' => $adminRole->id,
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('role_id');

        $this->assertDatabaseMissing('users', ['email' => 'another-admin@test.local']);
    }

    public function test_users_index_supports_live_ajax_filtering(): void
    {
        $admin = $this->createAdmin();
        $this->createStudent();

        $this->actingAs($admin)
            ->getJson('/admin/users?search=Test+Student')
            ->assertOk()
            ->assertJsonStructure(['html', 'total', 'first', 'last'])
            ->assertJsonPath('total', 1);
    }
}
