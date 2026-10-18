<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Scholarship;
use App\Models\ScholarshipMatchingWeight;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScholarshipSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);

        ScholarshipMatchingWeight::create([
            'academic_weight' => 30, 'financial_weight' => 25, 'program_weight' => 20,
            'year_level_weight' => 15, 'document_readiness_weight' => 10, 'is_active' => true,
        ]);
    }

    private function student(): User
    {
        $unique = uniqid();
        $u = User::create([
            'name' => 'S', 'email' => "s_{$unique}@t.l",
            'password' => Hash::make('p'),
            'role_id' => Role::where('name', 'student')->first()->id,
            'is_active' => true,
        ]);
        $u->forceFill(['email_verified_at' => now()])->save();
        StudentProfile::create([
            'user_id' => $u->id, 'student_number' => "S-{$unique}",
            'first_name' => 'T', 'last_name' => 'S',
            'year_level' => 3, 'gwa' => 1.50,
            'program' => 'BSIT', 'family_income' => 20000,
        ]);
        return $u;
    }

    private function counselor(): User
    {
        $unique = uniqid();
        return User::create([
            'name' => 'C', 'email' => "c_{$unique}@t.l",
            'password' => Hash::make('p'),
            'role_id' => Role::where('name', 'counselor')->first()->id,
            'is_active' => true,
        ]);
    }

    private function scholarship(array $attrs = []): Scholarship
    {
        return Scholarship::create(array_merge([
            'title' => 'Test Scholarship',
            'description' => 'A test scholarship description',
            'provider' => 'ISU',
            'scholarship_type' => 'Merit-Based',
            'application_start_date' => now()->subDay(),
            'application_deadline' => now()->addMonth(),
            'status' => 'open',
        ], $attrs));
    }

    // ── A. Empty Search ──────────────────────────────

    public function test_empty_search_shows_available_scholarships(): void
    {
        $this->scholarship(['title' => 'Alpha']);
        $this->scholarship(['title' => 'Beta']);

        $this->actingAs($this->student());
        $this->get('/student/scholarships')
             ->assertOk()
             ->assertSee('Alpha')
             ->assertSee('Beta');
    }

    // ── B. Exact Keyword ─────────────────────────────

    public function test_keyword_search_finds_matching_title(): void
    {
        $this->scholarship(['title' => 'Academic Excellence Grant']);
        $this->scholarship(['title' => 'Community Service Award']);

        $this->actingAs($this->student());
        $this->get('/student/scholarships?search=Academic+Excellence')
             ->assertSee('Academic Excellence Grant')
             ->assertDontSee('Community Service Award');
    }

    // ── C. Partial Keyword ───────────────────────────

    public function test_partial_keyword_search(): void
    {
        $this->scholarship(['title' => 'Financial Assistance Fund']);
        $this->scholarship(['title' => 'Sports Achievement Grant']);

        $this->actingAs($this->student());
        $this->get('/student/scholarships?search=financ')
             ->assertSee('Financial Assistance Fund')
             ->assertDontSee('Sports Achievement Grant');
    }

    // ── D. Multi-Keyword ─────────────────────────────

    public function test_multi_keyword_search_across_fields(): void
    {
        $this->scholarship([
            'title' => 'Excellence Fund',
            'description' => 'Financial assistance for students with excellent grades',
        ]);
        $this->scholarship(['title' => 'Sports Grant']);

        $this->actingAs($this->student());
        $this->get('/student/scholarships?search=financial+excellence')
             ->assertSee('Excellence Fund')
             ->assertDontSee('Sports Grant');
    }

    // ── E. Multiple Filters ──────────────────────────

    public function test_multiple_filters_combined(): void
    {
        $this->scholarship([
            'title' => 'BSIT Grant',
            'scholarship_type' => 'Merit-Based',
            'eligible_programs' => ['BSIT'],
            'eligible_year_levels' => [3],
        ]);
        $this->scholarship([
            'title' => 'BSN Grant',
            'scholarship_type' => 'Merit-Based',
            'eligible_programs' => ['BSN'],
            'eligible_year_levels' => [3],
        ]);

        $this->actingAs($this->student());
        $this->get('/student/scholarships?program=BSIT&year_level=3&type=Merit-Based')
             ->assertSee('BSIT Grant')
             ->assertDontSee('BSN Grant');
    }

    // ── F. No Results ────────────────────────────────

    public function test_no_results_shows_empty_state(): void
    {
        $this->scholarship(['title' => 'Existing']);

        $this->actingAs($this->student());
        $this->get('/student/scholarships?search=nonexistent-keyword-xyz')
             ->assertSee('No scholarships found');
    }

    // ── G. Invalid Sort Falls Back ───────────────────

    public function test_invalid_sort_falls_back_to_default(): void
    {
        $this->scholarship(['title' => 'Test']);
        $this->actingAs($this->student());
        $this->get('/student/scholarships?sort=malicious_sort')->assertOk();
    }

    // ── K. Authorization ─────────────────────────────

    public function test_guest_cannot_access_search(): void
    {
        $this->get('/student/scholarships')->assertRedirect('/login');
    }

    public function test_counselor_cannot_access_search(): void
    {
        $this->actingAs($this->counselor());
        $this->get('/student/scholarships')->assertStatus(403);
    }

    public function test_student_can_access_search(): void
    {
        $this->actingAs($this->student());
        $this->get('/student/scholarships')->assertOk();
    }

    // ── L. Input Safety ──────────────────────────────

    public function test_sql_injection_attempt_is_safe(): void
    {
        $this->scholarship(['title' => 'Safe']);
        $this->actingAs($this->student());
        $this->get("/student/scholarships?search=' OR 1=1 --")->assertOk();
    }

    public function test_whitespace_only_search_shows_all(): void
    {
        $this->scholarship(['title' => 'Alpha']);
        $this->actingAs($this->student());
        $this->get('/student/scholarships?search=' . urlencode('   '))
             ->assertOk()
             ->assertSee('Alpha');
    }

    public function test_very_long_search_input_is_truncated(): void
    {
        $this->scholarship(['title' => 'Test']);
        $this->actingAs($this->student());
        $this->get('/student/scholarships?search=' . str_repeat('a', 500))->assertOk();
    }

    // ── H. Deadline Filter ───────────────────────────

    public function test_deadline_filter_within_7_days(): void
    {
        $this->scholarship(['title' => 'Closing Soon', 'application_deadline' => now()->addDays(3)]);
        $this->scholarship(['title' => 'Later', 'application_deadline' => now()->addDays(60)]);

        $this->actingAs($this->student());
        $this->get('/student/scholarships?deadline=within_7_days')
             ->assertSee('Closing Soon')
             ->assertDontSee('Later');
    }

    // ── I. Match Score Sort ──────────────────────────

    public function test_match_score_sort_works(): void
    {
        // Create one perfectly matching scholarship
        $this->scholarship([
            'title' => 'Perfect Match',
            'minimum_gwa' => 1.75,
            'maximum_family_income' => 30000,
            'eligible_programs' => ['BSIT'],
            'eligible_year_levels' => [3],
        ]);

        // Create a scholarship for which student is not eligible
        $this->scholarship([
            'title' => 'Not For Me',
            'eligible_programs' => ['BSN'],
        ]);

        $this->actingAs($this->student());
        $this->get('/student/scholarships?sort=match_score')
             ->assertOk()
             ->assertSee('Perfect Match');
    }
}