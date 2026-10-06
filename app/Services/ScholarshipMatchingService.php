<?php

namespace App\Services;

use App\DataObjects\EligibilityEvaluationResult;
use App\DataObjects\FactorResult;
use App\DataObjects\ScholarshipMatchResult;
use App\Enums\OverallEligibility;
use App\Models\ApplicationDocument;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMatchingWeight;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class ScholarshipMatchingService
{
    public function __construct(
        protected ScholarshipEligibilityService $eligibilityService
    ) {}

    /**
     * Generate ranked scholarship recommendations for a student.
     */
    public function recommendForStudent(StudentProfile $profile): Collection
    {
        $weights = ScholarshipMatchingWeight::active();

        if (! $weights) {
            return collect();
        }

        // Retrieve available scholarships with eager loading
        $scholarships = Scholarship::availableForStudents()
            ->with('requirements')
            ->get();

        // Get student's existing applications
        $applications = ScholarshipApplication::where('student_profile_id', $profile->id)
            ->get()
            ->keyBy('scholarship_id');

        $results = collect();

        foreach ($scholarships as $scholarship) {
            $application = $applications->get($scholarship->id);

            // Skip scholarships with terminal applications
            if ($application && in_array($application->getRawOriginal('status'), ['approved', 'rejected'])) {
                continue;
            }

            $result = $this->evaluateMatch($profile, $scholarship, $application, $weights);
            $results->push($result);
        }

        return $this->rank($results);
    }

    /**
     * Evaluate a single scholarship match for a student.
     */
    public function evaluateMatch(
        StudentProfile $profile,
        Scholarship $scholarship,
        ?ScholarshipApplication $application,
        ?ScholarshipMatchingWeight $weights = null
    ): ScholarshipMatchResult {
        $weights = $weights ?? ScholarshipMatchingWeight::active();

        // 1. Eligibility check (reuse existing engine)
        $eligibilityResult = $this->eligibilityService->evaluate($profile, $scholarship, $application);

        $eligibility = $eligibilityResult->overall;
        $eligibilityLabel = $eligibility === OverallEligibility::ELIGIBLE ? 'Eligible' : 'Not Eligible';

        // 2. Calculate factor scores
        $factors = $this->calculateFactors($profile, $scholarship, $application, $weights, $eligibilityResult);

        // 3. Calculate weighted score
        $matchScore = null;
        if ($eligibility === OverallEligibility::ELIGIBLE) {
            $matchScore = $this->calculateScore($factors);
        }

        // 4. Generate reasons
        $reasons = $this->generateReasons($factors, $eligibility, $eligibilityResult);

        $appStatus = null;
        $appId = null;
        if ($application) {
            $appStatus = is_object($application->status) ? $application->status->value : (string) $application->status;
            $appId = $application->id;
        }

        return new ScholarshipMatchResult(
            scholarshipId: $scholarship->id,
            scholarshipTitle: $scholarship->title,
            provider: $scholarship->provider,
            scholarshipType: $scholarship->scholarship_type,
            deadline: $scholarship->application_deadline->format('M d, Y'),
            eligibilityLabel: $eligibilityLabel,
            eligibility: $eligibility,
            matchScore: $matchScore !== null ? round($matchScore * 100, 2) : null,
            rank: null,
            factors: $factors,
            reasons: $reasons,
            existingApplicationStatus: $appStatus,
            existingApplicationId: $appId,
            eligibilityResult: $eligibilityResult,
        );
    }

    /**
     * Calculate individual factor results.
     *
     * @return FactorResult[]
     */
    protected function calculateFactors(
        StudentProfile $profile,
        Scholarship $scholarship,
        ?ScholarshipApplication $application,
        ScholarshipMatchingWeight $weights,
        EligibilityEvaluationResult $eligibility
    ): array {
        $w = $weights->toDecimalArray();

        return [
            $this->scoreAcademic($profile, $scholarship, $w['academic']),
            $this->scoreFinancial($profile, $scholarship, $w['financial']),
            $this->scoreProgram($profile, $scholarship, $w['program']),
            $this->scoreYearLevel($profile, $scholarship, $w['year_level']),
            $this->scoreDocumentReadiness($scholarship, $application, $w['document_readiness']),
        ];
    }

    protected function scoreAcademic(StudentProfile $p, Scholarship $s, float $weight): FactorResult
    {
        if ($s->minimum_gwa === null) {
            return new FactorResult('academic', 'Academic Qualification', $weight, false, null, null, 'not_applicable', 'No GWA requirement configured.');
        }

        if ($p->gwa === null) {
            return new FactorResult('academic', 'Academic Qualification', $weight, true, 0.0, 0.0, 'not_met', 'GWA not available in your profile.');
        }

        $met = (float) $p->gwa <= (float) $s->minimum_gwa;
        $score = $met ? 1.0 : 0.0;

        return new FactorResult(
            'academic', 'Academic Qualification', $weight, true, $score, $score * $weight,
            $met ? 'met' : 'not_met',
            $met ? 'Your GWA meets the configured academic requirement.' : 'Your GWA does not meet the configured academic requirement.'
        );
    }

    protected function scoreFinancial(StudentProfile $p, Scholarship $s, float $weight): FactorResult
    {
        if ($s->maximum_family_income === null) {
            return new FactorResult('financial', 'Financial Eligibility', $weight, false, null, null, 'not_applicable', 'No income limit configured.');
        }

        if ($p->family_income === null) {
            return new FactorResult('financial', 'Financial Eligibility', $weight, true, 0.0, 0.0, 'not_met', 'Family income not available in your profile.');
        }

        $met = (float) $p->family_income <= (float) $s->maximum_family_income;
        $score = $met ? 1.0 : 0.0;

        return new FactorResult(
            'financial', 'Financial Eligibility', $weight, true, $score, $score * $weight,
            $met ? 'met' : 'not_met',
            $met ? 'Your family income meets the configured limit.' : 'Your family income exceeds the configured limit.'
        );
    }

    protected function scoreProgram(StudentProfile $p, Scholarship $s, float $weight): FactorResult
    {
        if (empty($s->eligible_programs)) {
            return new FactorResult('program', 'Program Match', $weight, false, null, null, 'not_applicable', 'Open to all programs.');
        }

        if (blank($p->program)) {
            return new FactorResult('program', 'Program Match', $weight, true, 0.0, 0.0, 'not_met', 'Program not set in your profile.');
        }

        $normalized = array_map(fn ($v) => strtoupper(trim($v)), $s->eligible_programs);
        $met = in_array(strtoupper(trim($p->program)), $normalized, true);
        $score = $met ? 1.0 : 0.0;

        return new FactorResult(
            'program', 'Program Match', $weight, true, $score, $score * $weight,
            $met ? 'met' : 'not_met',
            $met ? 'Your program is eligible.' : 'Your program is not among the eligible programs.'
        );
    }

    protected function scoreYearLevel(StudentProfile $p, Scholarship $s, float $weight): FactorResult
    {
        if (empty($s->eligible_year_levels)) {
            return new FactorResult('year_level', 'Year-Level Match', $weight, false, null, null, 'not_applicable', 'Open to all year levels.');
        }

        if ($p->year_level === null) {
            return new FactorResult('year_level', 'Year-Level Match', $weight, true, 0.0, 0.0, 'not_met', 'Year level not set in your profile.');
        }

        $normalized = array_map('intval', $s->eligible_year_levels);
        $met = in_array((int) $p->year_level, $normalized, true);
        $score = $met ? 1.0 : 0.0;

        return new FactorResult(
            'year_level', 'Year-Level Match', $weight, true, $score, $score * $weight,
            $met ? 'met' : 'not_met',
            $met ? 'Your year level is eligible.' : 'Your year level is not among the eligible year levels.'
        );
    }

    protected function scoreDocumentReadiness(Scholarship $s, ?ScholarshipApplication $app, float $weight): FactorResult
    {
        $requiredCount = $s->requirements->where('is_required', true)->count();

        if ($requiredCount === 0) {
            return new FactorResult('document_readiness', 'Document Readiness', $weight, false, null, null, 'not_applicable', 'No required documents configured.');
        }

        if (! $app) {
            return new FactorResult('document_readiness', 'Document Readiness', $weight, false, null, null, 'not_applicable', 'Documents can be prepared after starting an application.');
        }

        $app->loadMissing('documents');
        $requiredIds = $s->requirements->where('is_required', true)->pluck('id');

        $uploadedDocs = $app->documents->whereIn('scholarship_requirement_id', $requiredIds);

        $appStatus = is_object($app->status) ? $app->status->value : (string) $app->status;

        if ($appStatus === 'document_verification' || $appStatus === 'approved') {
            $readyCount = $uploadedDocs->where('verification_status', 'verified')->count();
        } else {
            $readyCount = $uploadedDocs->count();
        }

        $score = $requiredCount > 0 ? $readyCount / $requiredCount : 0.0;
        $status = $score >= 1.0 ? 'met' : ($score > 0 ? 'partial' : 'not_met');
        $explanation = "{$readyCount} of {$requiredCount} required documents ready.";

        return new FactorResult(
            'document_readiness', 'Document Readiness', $weight, true,
            round($score, 4), round($score * $weight, 4),
            $status, $explanation
        );
    }

    /**
     * Calculate dynamically normalized score from factor results.
     *
     * @param  FactorResult[]  $factors
     */
    public function calculateScore(array $factors): ?float
    {
        $weightedSum = 0.0;
        $applicableWeightSum = 0.0;

        foreach ($factors as $f) {
            if ($f->isApplicable) {
                $weightedSum += $f->normalizedScore * $f->configuredWeight;
                $applicableWeightSum += $f->configuredWeight;
            }
        }

        if ($applicableWeightSum <= 0) {
            return null; // No applicable factors
        }

        $normalized = $weightedSum / $applicableWeightSum;

        return max(0.0, min(1.0, $normalized));
    }

    /**
     * Generate human-readable reasons.
     *
     * @param  FactorResult[]  $factors
     */
    protected function generateReasons(array $factors, OverallEligibility $eligibility, EligibilityEvaluationResult $eligibilityResult): array
    {
        $reasons = [];

        if ($eligibility === OverallEligibility::NOT_ELIGIBLE) {
            $reasons[] = 'You do not currently meet all mandatory eligibility requirements for this scholarship.';
            foreach ($eligibilityResult->criteria as $c) {
                if ($c->isMandatory && $c->status->value === 'not_met') {
                    $reasons[] = $c->explanation;
                }
            }
            return $reasons;
        }

        foreach ($factors as $f) {
            if ($f->isApplicable && $f->status === 'met') {
                $reasons[] = $f->explanation;
            }
        }

        foreach ($factors as $f) {
            if ($f->isApplicable && in_array($f->status, ['not_met', 'partial'])) {
                $reasons[] = $f->explanation;
            }
        }

        return $reasons;
    }

    /**
     * Rank eligible scholarship results by score with deterministic tie-breaking.
     */
    public function rank(Collection $results): Collection
    {
        // Separate eligible and ineligible
        $eligible = $results->filter(fn (ScholarshipMatchResult $r) => $r->isEligible());
        $ineligible = $results->filter(fn (ScholarshipMatchResult $r) => ! $r->isEligible());

        // Sort eligible by score (desc), deadline (asc), title (asc), id (asc)
        $ranked = $eligible->sort(function (ScholarshipMatchResult $a, ScholarshipMatchResult $b) {
            // Primary: score descending
            $scoreComp = ($b->matchScore ?? 0) <=> ($a->matchScore ?? 0);
            if ($scoreComp !== 0) return $scoreComp;

            // Tie-break 1: earlier deadline first
            $deadlineComp = strcmp($a->deadline, $b->deadline);
            if ($deadlineComp !== 0) return $deadlineComp;

            // Tie-break 2: title ascending
            $titleComp = strcmp($a->scholarshipTitle, $b->scholarshipTitle);
            if ($titleComp !== 0) return $titleComp;

            // Tie-break 3: id ascending
            return $a->scholarshipId <=> $b->scholarshipId;
        })->values();

        // Assign ranks
        $rankedWithPosition = $ranked->map(function (ScholarshipMatchResult $r, int $index) {
            return new ScholarshipMatchResult(
                scholarshipId: $r->scholarshipId,
                scholarshipTitle: $r->scholarshipTitle,
                provider: $r->provider,
                scholarshipType: $r->scholarshipType,
                deadline: $r->deadline,
                eligibilityLabel: $r->eligibilityLabel,
                eligibility: $r->eligibility,
                matchScore: $r->matchScore,
                rank: $index + 1,
                factors: $r->factors,
                reasons: $r->reasons,
                existingApplicationStatus: $r->existingApplicationStatus,
                existingApplicationId: $r->existingApplicationId,
                eligibilityResult: $r->eligibilityResult,
            );
        });

        return collect([
            'eligible'   => $rankedWithPosition,
            'ineligible' => $ineligible->values(),
        ]);
    }
}