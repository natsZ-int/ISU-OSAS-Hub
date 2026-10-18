<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EligibilityAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function scholarship(): Scholarship
    {
        return Scholarship::create([
            'title' => 'Test', 'description' => 'Test', 'provider' => 'ISU',
            'scholarship_type' => 'Merit', 'application_start_date' => now()->subDay(),
            'application_deadline' => now()->addMonth(), 'status' => 'open',
            'minimum_gwa' => 1.75,
        ]);
    }

    private function studentWithProfile(): User
    {
        $user = User::create([
            'name' => 'S', 'email' => 's@t.local', 'password' => Hash::make('pw'),
            'role_id' => Role::where('name', 'student')->first()->id, 'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        StudentProfile::create([
            'user_id' => $user->id, 'student_number' => '2025-1',
            'first_name' => 'S', 'last_name' => 'T', 'year_level' => 3, 'gwa' => 1.50,
        ]);
        return $user;
    }

    public function test_guest_cannot_view_eligibility(): void
    {
        $s = $this->scholarship();
        $this->get("/student/scholarships/{$s->id}/eligibility")->assertRedirect('/login');
    }

    public function test_student_can_view_own_eligibility(): void
    {
        $s = $this->scholarship();
        $this->actingAs($this->studentWithProfile());
        $this->get("/student/scholarships/{$s->id}/eligibility")->assertOk();
    }

    public function test_admin_cannot_use_student_eligibility_route(): void
    {
        $s = $this->scholarship();
        $admin = User::create([
            'name' => 'A', 'email' => 'a@t.local', 'password' => Hash::make('pw'),
            'role_id' => Role::where('name', 'admin')->first()->id, 'is_active' => true,
        ]);
        $this->actingAs($admin);
        $this->get("/student/scholarships/{$s->id}/eligibility")->assertStatus(403);
    }

    public function test_counselor_cannot_use_student_eligibility_route(): void
    {
        $s = $this->scholarship();
        $c = User::create([
            'name' => 'C', 'email' => 'c@t.local', 'password' => Hash::make('pw'),
            'role_id' => Role::where('name', 'counselor')->first()->id, 'is_active' => true,
        ]);
        $this->actingAs($c);
        $this->get("/student/scholarships/{$s->id}/eligibility")->assertStatus(403);
    }
}
