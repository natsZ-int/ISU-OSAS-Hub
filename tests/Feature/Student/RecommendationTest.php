<?php

namespace Tests\Feature\Student;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $counselor;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);
        $counselorRole = Role::create(['name' => 'counselor', 'label' => 'Counselor']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'is_active' => true]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $this->counselor = User::factory()->create(['role_id' => $counselorRole->id, 'is_active' => true]);

        StudentProfile::create([
            'user_id'           => $this->student->id,
            'student_number'    => '2026-FEAT-01',
            'first_name'        => 'Feature',
            'last_name'         => 'Test',
            'program'           => 'BSCS',
            'year_level'        => 3,
            'gwa'               => 1.50,
            'family_income'     => 100000,
            'enrollment_status' => 'enrolled',
        ]);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('student.scholarships.recommendations'))->assertRedirect(route('login'));
    }

    public function test_counselor_is_denied(): void
    {
        $this->actingAs($this->counselor)
            ->get(route('student.scholarships.recommendations'))
            ->assertStatus(403);
    }

    public function test_active_student_can_view_recommendations(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.scholarships.recommendations'))
            ->assertOk()
            ->assertSee('Recommended Scholarships');
    }

    public function test_recommendations_page_shows_disclaimer(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.scholarships.recommendations'))
            ->assertOk()
            ->assertSee('not a guarantee of scholarship approval');
    }
}