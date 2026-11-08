<?php

namespace Tests\Unit;

use App\Enums\EligibilityStatus;
use App\Enums\OverallEligibility;
use App\Models\Scholarship;
use App\Models\StudentProfile;
use App\Services\ScholarshipEligibilityService;
use PHPUnit\Framework\TestCase;

class ScholarshipEligibilityServiceTest extends TestCase
{
    private ScholarshipEligibilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScholarshipEligibilityService;
    }

    private function makeProfile(array $attrs = []): StudentProfile
    {
        $profile = new StudentProfile(array_merge([
            'gwa' => 1.50,
            'family_income' => 20000,
            'program' => 'BSIT',
            'year_level' => 3,
            'enrollment_status' => 'enrolled',
        ], $attrs));
        $profile->id = 1;
        $profile->exists = true;

        return $profile;
    }

    private function makeScholarship(array $attrs = []): Scholarship
    {
        $scholarship = new Scholarship(array_merge([
            'minimum_gwa' => 1.75,
            'maximum_family_income' => 30000,
            'eligible_programs' => ['BSIT', 'BSCS'],
            'eligible_year_levels' => [2, 3, 4],
            'required_enrollment_status' => 'enrolled',
        ], $attrs));
        $scholarship->id = 1;
        $scholarship->exists = true;

        return $scholarship;
    }

    // ── A. Fully Eligible ────────────────────────────

    public function test_fully_eligible_student(): void
    {
        $result = $this->service->evaluate($this->makeProfile(), $this->makeScholarship());

        $this->assertEquals(OverallEligibility::ELIGIBLE, $result->overall);
        foreach ($result->criteria as $c) {
            if ($c->isMandatory) {
                $this->assertEquals(EligibilityStatus::MET, $c->status, "Criterion {$c->criterionType} should be MET");
            }
        }
    }

    // ── B. Failing GWA ───────────────────────────────

    public function test_student_failing_gwa(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['gwa' => 2.00]),
            $this->makeScholarship()
        );

        $this->assertEquals(OverallEligibility::NOT_ELIGIBLE, $result->overall);
        $gwaCriterion = collect($result->criteria)->firstWhere('criterionType', 'minimum_gwa');
        $this->assertEquals(EligibilityStatus::NOT_MET, $gwaCriterion->status);
    }

    // ── C. Failing Income ────────────────────────────

    public function test_student_failing_income(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['family_income' => 50000]),
            $this->makeScholarship()
        );

        $this->assertEquals(OverallEligibility::NOT_ELIGIBLE, $result->overall);
        $c = collect($result->criteria)->firstWhere('criterionType', 'maximum_family_income');
        $this->assertEquals(EligibilityStatus::NOT_MET, $c->status);
    }

    // ── D. Failing Program ───────────────────────────

    public function test_student_failing_program(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['program' => 'BSN']),
            $this->makeScholarship()
        );

        $this->assertEquals(OverallEligibility::NOT_ELIGIBLE, $result->overall);
        $c = collect($result->criteria)->firstWhere('criterionType', 'eligible_programs');
        $this->assertEquals(EligibilityStatus::NOT_MET, $c->status);
    }

    // ── E. Multiple Failures ─────────────────────────

    public function test_student_failing_multiple_criteria(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['gwa' => 3.00, 'family_income' => 100000, 'program' => 'BSN']),
            $this->makeScholarship()
        );

        $this->assertEquals(OverallEligibility::NOT_ELIGIBLE, $result->overall);

        $failedCount = collect($result->criteria)
            ->filter(fn ($c) => $c->status === EligibilityStatus::NOT_MET)
            ->count();

        $this->assertGreaterThanOrEqual(3, $failedCount);
    }

    // ── F. Scholarship with no optional criterion ────

    public function test_scholarship_without_income_limit(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['family_income' => 999999]),
            $this->makeScholarship(['maximum_family_income' => null])
        );

        $c = collect($result->criteria)->firstWhere('criterionType', 'maximum_family_income');
        $this->assertEquals(EligibilityStatus::NOT_APPLICABLE, $c->status);
        $this->assertEquals(OverallEligibility::ELIGIBLE, $result->overall);
    }

    public function test_scholarship_open_to_all_programs(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['program' => 'BSN']),
            $this->makeScholarship(['eligible_programs' => null])
        );

        $c = collect($result->criteria)->firstWhere('criterionType', 'eligible_programs');
        $this->assertEquals(EligibilityStatus::NOT_APPLICABLE, $c->status);
    }

    // ── G. Missing Student Data ──────────────────────

    public function test_missing_gwa_when_required(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['gwa' => null]),
            $this->makeScholarship()
        );

        $c = collect($result->criteria)->firstWhere('criterionType', 'minimum_gwa');
        $this->assertEquals(EligibilityStatus::NOT_MET, $c->status);
        $this->assertEquals(OverallEligibility::NOT_ELIGIBLE, $result->overall);
    }

    public function test_missing_income_when_required(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['family_income' => null]),
            $this->makeScholarship()
        );

        $c = collect($result->criteria)->firstWhere('criterionType', 'maximum_family_income');
        $this->assertEquals(EligibilityStatus::NOT_MET, $c->status);
    }

    public function test_missing_enrollment_status_when_required(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['enrollment_status' => 'unknown']),
            $this->makeScholarship()
        );

        $c = collect($result->criteria)->firstWhere('criterionType', 'enrollment_status');
        $this->assertEquals(EligibilityStatus::NOT_MET, $c->status);
    }

    // ── Program normalization ────────────────────────

    public function test_program_normalization_case_insensitive(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['program' => 'bsit']),
            $this->makeScholarship(['eligible_programs' => ['BSIT']])
        );

        $c = collect($result->criteria)->firstWhere('criterionType', 'eligible_programs');
        $this->assertEquals(EligibilityStatus::MET, $c->status);
    }

    // ── Boundary GWA test ────────────────────────────

    public function test_gwa_exactly_at_threshold_is_met(): void
    {
        $result = $this->service->evaluate(
            $this->makeProfile(['gwa' => 1.75]),
            $this->makeScholarship(['minimum_gwa' => 1.75])
        );

        $c = collect($result->criteria)->firstWhere('criterionType', 'minimum_gwa');
        $this->assertEquals(EligibilityStatus::MET, $c->status);
    }
}
