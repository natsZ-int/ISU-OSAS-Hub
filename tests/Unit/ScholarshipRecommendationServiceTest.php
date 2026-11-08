<?php

namespace Tests\Unit;

use App\Enums\RecommendationCategory;
use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMatchingWeight;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ScholarshipRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScholarshipRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ScholarshipRecommendationService $service;

    protected User $admin;

    protected User $studentUser;

    protected StudentProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ScholarshipRecommendationService::class);

        $adminRole = Role::create(['name' => 'admin', 'label' => 'Administrator']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->studentUser = User::factory()->create(['role_id' => $studentRole->id]);

        $this->profile = StudentProfile::create([
            'user_id' => $this->studentUser->id,
            'student_number' => '2026-0001',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'program' => 'BSCS',
            'year_level' => 3,
            'gwa' => 1.50,
            'family_income' => 100000,
            'enrollment_status' => 'enrolled',
        ]);

        // Create active matching weights
        ScholarshipMatchingWeight::create([
            'academic_weight' => 30,
            'financial_weight' => 25,
            'program_weight' => 20,
            'year_level_weight' => 15,
            'document_readiness_weight' => 10,
            'is_active' => true,
        ]);
    }

    protected function createScholarship(array $overrides = []): Scholarship
    {
        return Scholarship::create(array_merge([
            'title' => 'Test Scholarship',
            'description' => 'Test description',
            'provider' => 'CHED',
            'scholarship_type' => 'academic',
            'status' => 'open',
            'application_start_date' => now()->subDays(10),
            'application_deadline' => now()->addDays(30),
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_eligible_student_receives_eligible_recommendation(): void
    {
        $scholarship = $this->createScholarship([
            'minimum_gwa' => 2.0,
            'maximum_family_income' => 200000,
            'eligible_programs' => ['BSCS'],
            'eligible_year_levels' => [3, 4],
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertCount(1, $results['eligible']);
        $this->assertCount(0, $results['not_eligible']);

        $rec = $results['eligible']->first();
        $this->assertEquals(RecommendationCategory::ELIGIBLE_RECOMMENDATION, $rec->category);
        $this->assertNotNull($rec->matchScore);
        $this->assertEquals(1, $rec->rank);
        $this->assertNotEmpty($rec->reasons);
    }

    public function test_ineligible_gwa_produces_not_eligible_category(): void
    {
        $this->createScholarship([
            'title' => 'High GWA Scholarship',
            'minimum_gwa' => 1.25, // Student has 1.50 (higher = worse)
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertCount(0, $results['eligible']);
        $this->assertCount(1, $results['not_eligible']);

        $rec = $results['not_eligible']->first();
        $this->assertEquals(RecommendationCategory::NOT_ELIGIBLE, $rec->category);
        $this->assertNull($rec->rank);

        // Explanation must mention GWA
        $reasonText = implode(' ', $rec->reasons);
        $this->assertStringContainsStringIgnoringCase('GWA', $reasonText);
    }

    public function test_ineligible_income_produces_not_eligible_category(): void
    {
        $this->createScholarship([
            'title' => 'Low Income Grant',
            'maximum_family_income' => 50000, // Student has 100000
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertCount(1, $results['not_eligible']);
        $rec = $results['not_eligible']->first();
        $this->assertEquals(RecommendationCategory::NOT_ELIGIBLE, $rec->category);
    }

    public function test_ineligible_program_produces_not_eligible_category(): void
    {
        $this->createScholarship([
            'title' => 'Engineering Only',
            'eligible_programs' => ['BSCE', 'BSEE'],
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertCount(1, $results['not_eligible']);
    }

    public function test_draft_application_with_missing_docs_produces_potential_match(): void
    {
        $scholarship = $this->createScholarship([
            'minimum_gwa' => 2.0,
        ]);

        $req = $scholarship->requirements()->create([
            'requirement_name' => 'Income Tax Return',
            'is_required' => true,
            'display_order' => 1,
        ]);

        ScholarshipApplication::create([
            'application_number' => 'APP-DRAFT-01',
            'student_profile_id' => $this->profile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'draft',
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertCount(1, $results['potential']);
        $rec = $results['potential']->first();
        $this->assertEquals(RecommendationCategory::POTENTIAL_MATCH, $rec->category);
        $this->assertContains('Income Tax Return', $rec->requirementReadiness['missing']);
    }

    public function test_eligible_scholarship_with_active_application_is_still_recommended(): void
    {
        $scholarship = $this->createScholarship(['minimum_gwa' => 2.0]);

        $application = ScholarshipApplication::create([
            'application_number' => 'APP-ACTIVE-01',
            'student_profile_id' => $this->profile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        // The scholarship must still appear even though an active application
        // exists, matching how it is shown in Browse Scholarships.
        $this->assertCount(1, $results['eligible']);
        $this->assertCount(0, $results['not_eligible']);

        $rec = $results['eligible']->first();
        $this->assertSame($scholarship->id, $rec->scholarshipId);
        $this->assertTrue($rec->hasExistingActiveApplication());
        $this->assertSame($application->id, $rec->existingApplicationId);
        $this->assertSame('submitted', $rec->existingApplicationStatus);
    }

    public function test_terminal_application_is_excluded(): void
    {
        $scholarship = $this->createScholarship(['minimum_gwa' => 2.0]);

        ScholarshipApplication::create([
            'application_number' => 'APP-TERM-01',
            'student_profile_id' => $this->profile->id,
            'scholarship_id' => $scholarship->id,
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertCount(0, $results['eligible']);
    }

    public function test_ranking_by_score_descending(): void
    {
        // Scholarship A: high match (all criteria met)
        $this->createScholarship([
            'title' => 'Scholarship A',
            'minimum_gwa' => 2.0,
            'maximum_family_income' => 200000,
            'eligible_programs' => ['BSCS'],
            'eligible_year_levels' => [3],
        ]);

        // Scholarship B: lower match (only GWA met, no program/level restriction means N/A)
        $this->createScholarship([
            'title' => 'Scholarship B',
            'minimum_gwa' => 2.0,
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertGreaterThanOrEqual(2, $results['eligible']->count());
        $first = $results['eligible']->first();
        $second = $results['eligible']->get(1);
        $this->assertGreaterThanOrEqual($second->matchScore ?? 0, $first->matchScore ?? 0);
        $this->assertEquals(1, $first->rank);
        $this->assertEquals(2, $second->rank);
    }

    public function test_deadline_tiebreaker(): void
    {
        $this->createScholarship([
            'title' => 'Later Deadline',
            'minimum_gwa' => 2.0,
            'application_deadline' => now()->addDays(60),
        ]);

        $this->createScholarship([
            'title' => 'Earlier Deadline',
            'minimum_gwa' => 2.0,
            'application_deadline' => now()->addDays(10),
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $this->assertGreaterThanOrEqual(2, $results['eligible']->count());
        // Both have same score (only GWA factor applicable, both met).
        // Earlier deadline should rank first.
        $first = $results['eligible']->first();
        $this->assertEquals('Earlier Deadline', $first->scholarshipTitle);
    }

    public function test_unavailable_scholarships_are_not_recommended(): void
    {
        $this->createScholarship(['title' => 'Draft', 'status' => 'draft']);
        $this->createScholarship(['title' => 'Closed', 'status' => 'closed']);
        $this->createScholarship(['title' => 'Archived', 'status' => 'archived']);
        $this->createScholarship([
            'title' => 'Future',
            'status' => 'open',
            'application_start_date' => now()->addDays(10),
            'application_deadline' => now()->addDays(30),
        ]);

        $results = $this->service->recommendForStudent($this->profile);

        $total = $results['eligible']->count() + $results['potential']->count() + $results['not_eligible']->count();
        $this->assertEquals(0, $total);
    }

    public function test_missing_profile_data_produces_not_eligible(): void
    {
        $profileNoGwa = StudentProfile::create([
            'user_id' => User::factory()->create(['role_id' => Role::where('name', 'student')->first()->id])->id,
            'student_number' => '2026-0099',
            'first_name' => 'No',
            'last_name' => 'Gwa',
            'program' => 'BSCS',
            'year_level' => 3,
            'gwa' => null,
            'family_income' => 100000,
            'enrollment_status' => 'enrolled',
        ]);

        $this->createScholarship(['minimum_gwa' => 2.0]);

        $results = $this->service->recommendForStudent($profileNoGwa);

        $this->assertCount(1, $results['not_eligible']);
        $reasonText = implode(' ', $results['not_eligible']->first()->reasons);
        $this->assertStringContainsStringIgnoringCase('GWA', $reasonText);
    }

    public function test_every_recommendation_has_explanations(): void
    {
        $this->createScholarship(['title' => 'Eligible One', 'minimum_gwa' => 2.0]);
        $this->createScholarship(['title' => 'Not Eligible One', 'minimum_gwa' => 1.0]);

        $results = $this->service->recommendForStudent($this->profile);

        $all = $results['eligible']->merge($results['potential'])->merge($results['not_eligible']);
        foreach ($all as $rec) {
            $this->assertNotEmpty($rec->reasons, "Recommendation for {$rec->scholarshipTitle} has no reasons.");
        }
    }
}
