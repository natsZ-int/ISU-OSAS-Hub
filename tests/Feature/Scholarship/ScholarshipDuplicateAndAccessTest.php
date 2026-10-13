<?php

namespace Tests\Feature\Scholarship;

use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScholarshipDuplicateAndAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected StudentProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole   = Role::create(['name' => 'admin',   'label' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);

        $this->admin   = User::factory()->create(['role_id' => $adminRole->id,   'is_active' => true]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $this->profile = StudentProfile::create([
            'user_id'           => $this->student->id,
            'student_number'    => '2026-DUP-01',
            'first_name'        => 'Dup',
            'last_name'         => 'Test',
            'program'           => 'BSCS',
            'year_level'        => 3,
            'gwa'               => 1.50,
            'enrollment_status' => 'enrolled',
        ]);
    }

    public function test_student_cannot_create_duplicate_application_for_same_scholarship(): void
    {
        $scholarship = Scholarship::create([
            'title'                  => 'Unique Grant',
            'description'            => 'desc',
            'provider'               => 'CHED',
            'scholarship_type'       => 'academic',
            'status'                 => 'open',
            'application_start_date' => now()->subDays(5),
            'application_deadline'   => now()->addDays(30),
            'created_by'             => $this->admin->id,
        ]);

        // First application
        $this->actingAs($this->student)
            ->post(route('student.applications.store', $scholarship))
            ->assertRedirect();

        // Attempt duplicate
        $this->actingAs($this->student)
            ->post(route('student.applications.store', $scholarship))
            ->assertRedirect();

        // Only one application should exist
        $this->assertEquals(1, ScholarshipApplication::where('student_profile_id', $this->profile->id)
            ->where('scholarship_id', $scholarship->id)
            ->count());
    }

    public function test_student_cannot_view_archived_scholarship_in_index(): void
    {
        Scholarship::create([
            'title'                  => 'Archived Grant',
            'description'            => 'desc',
            'provider'               => 'CHED',
            'scholarship_type'       => 'academic',
            'status'                 => 'archived',
            'application_start_date' => now()->subDays(5),
            'application_deadline'   => now()->addDays(30),
            'created_by'             => $this->admin->id,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.scholarships.index'))
            ->assertOk()
            ->assertDontSee('Archived Grant');
    }
}