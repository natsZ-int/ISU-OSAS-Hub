<?php

namespace Tests\Unit;

use App\DataObjects\FactorResult;
use App\Models\Scholarship;
use App\Models\ScholarshipMatchingWeight;
use App\Models\StudentProfile;
use App\Services\ScholarshipEligibilityService;
use App\Services\ScholarshipMatchingService;
use Tests\TestCase;

class ScholarshipMatchingServiceTest extends TestCase
{
    private ScholarshipMatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $eligibility = new ScholarshipEligibilityService;
        $this->service = new ScholarshipMatchingService($eligibility);
    }

    private function profile(array $a = []): StudentProfile
    {
        $p = new StudentProfile(array_merge([
            'gwa' => 1.50,
            'family_income' => 20000,
            'program' => 'BSIT',
            'year_level' => 3,
            'enrollment_status' => 'enrolled',
        ], $a));
        $p->id = 1;
        $p->exists = true;

        return $p;
    }

    private function scholarship(array $a = []): Scholarship
    {
        $s = new Scholarship(array_merge([
            'title' => 'Test',
            'provider' => 'ISU',
            'scholarship_type' => 'Merit',
            'minimum_gwa' => 1.75,
            'maximum_family_income' => 30000,
            'eligible_programs' => ['BSIT', 'BSCS'],
            'eligible_year_levels' => [2, 3, 4],
            'required_enrollment_status' => null,
            'application_start_date' => now()->subDay(),
            'application_deadline' => now()->addMonth(),
            'status' => 'open',
        ], $a));
        $s->id = 1;
        $s->exists = true;
        $s->setRelation('requirements', collect());

        return $s;
    }

    private function weights(array $a = []): ScholarshipMatchingWeight
    {
        return new ScholarshipMatchingWeight(array_merge([
            'academic_weight' => 30,
            'financial_weight' => 25,
            'program_weight' => 20,
            'year_level_weight' => 15,
            'document_readiness_weight' => 10,
            'is_active' => true,
        ], $a));
    }

    // ── A. Perfect Match ─────────────────────────────

    public function test_perfect_match(): void
    {
        $result = $this->service->evaluateMatch(
            $this->profile(),
            $this->scholarship(),
            null,
            $this->weights()
        );

        $this->assertTrue($result->isEligible());
        $this->assertEquals(100.00, $result->matchScore);
    }

    // ── D. Ineligible Excluded ───────────────────────

    public function test_ineligible_excluded(): void
    {
        $result = $this->service->evaluateMatch(
            $this->profile(['gwa' => 3.00]),
            $this->scholarship(),
            null,
            $this->weights()
        );

        $this->assertFalse($result->isEligible());
        $this->assertNull($result->matchScore);
    }

    // ── H. Non-Applicable Normalization ──────────────

    public function test_non_applicable_normalization(): void
    {
        $result = $this->service->evaluateMatch(
            $this->profile(),
            $this->scholarship([
                'maximum_family_income' => null,
                'eligible_programs' => null,
                'eligible_year_levels' => null,
            ]),
            null,
            $this->weights()
        );

        $this->assertTrue($result->isEligible());
        $this->assertEquals(100.00, $result->matchScore);
    }

    // ── I. No Applicable Factors ─────────────────────

    public function test_no_applicable_factors(): void
    {
        $result = $this->service->evaluateMatch(
            $this->profile(),
            $this->scholarship([
                'minimum_gwa' => null,
                'maximum_family_income' => null,
                'eligible_programs' => null,
                'eligible_year_levels' => null,
            ]),
            null,
            $this->weights()
        );

        $this->assertTrue($result->isEligible());
        $this->assertNull($result->matchScore);
    }

    // ── E. Changed Weights ──────────────────────────

    public function test_changed_weights_affect_score(): void
    {
        $w1 = $this->weights([
            'academic_weight' => 100,
            'financial_weight' => 0,
            'program_weight' => 0,
            'year_level_weight' => 0,
            'document_readiness_weight' => 0,
        ]);

        $r1 = $this->service->evaluateMatch(
            $this->profile(),
            $this->scholarship(),
            null,
            $w1
        );
        $this->assertEquals(100.00, $r1->matchScore);

        $r2 = $this->service->evaluateMatch(
            $this->profile(['gwa' => 2.00]),
            $this->scholarship(['minimum_gwa' => 1.50]),
            null,
            $w1
        );
        $this->assertFalse($r2->isEligible());
    }

    // ── Score Calculation (Pure Logic) ───────────────

    public function test_score_calculation_with_factors(): void
    {
        $factors = [
            new FactorResult('a', 'A', 0.5, true, 1.0, 0.5, 'met', ''),
            new FactorResult('b', 'B', 0.5, true, 0.5, 0.25, 'partial', ''),
        ];

        $score = $this->service->calculateScore($factors);
        $this->assertEquals(0.75, $score);
    }

    public function test_score_null_when_no_applicable(): void
    {
        $factors = [
            new FactorResult('a', 'A', 0.5, false, null, null, 'not_applicable', ''),
        ];

        $this->assertNull($this->service->calculateScore($factors));
    }
}
