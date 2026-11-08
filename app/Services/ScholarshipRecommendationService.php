<?php

namespace App\Services;

use App\DataObjects\EligibilityEvaluationResult;
use App\DataObjects\ScholarshipMatchResult;
use App\DataObjects\ScholarshipRecommendationResult;
use App\Enums\OverallEligibility;
use App\Enums\RecommendationCategory;
use App\Models\Scholarship;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipMatchingWeight;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

class ScholarshipRecommendationService
{
    public function __construct(
        protected ScholarshipEligibilityService $eligibilityService,
        protected ScholarshipMatchingService $matchingService,
    ) {}

    /**
     * Generate categorized, ranked recommendations for a student.
     *
     * @return array{eligible: Collection, potential: Collection, not_eligible: Collection}
     */
    public function recommendForStudent(StudentProfile $profile): array
    {
        $scholarships = Scholarship::availableForStudents()
            ->with(['requirements' => fn ($q) => $q->where('is_required', true)])
            ->get();

        // Eager load everything the per-scholarship evaluation reads so the
        // loop below stays at a fixed number of queries as applications grow.
        $applications = ScholarshipApplication::where('student_profile_id', $profile->id)
            ->with(['documents', 'scholarship.requirements'])
            ->get()
            ->keyBy('scholarship_id');

        // Matching weights change rarely and are identical for every
        // scholarship: read them once instead of once per scholarship.
        // Fall back to the built-in defaults when none have been saved.
        $weights = ScholarshipMatchingWeight::activeOrDefault();

        $recommendations = collect();

        foreach ($scholarships as $scholarship) {
            $application = $applications->get($scholarship->id);

            // Skip terminal applications (approved/rejected): those scholarships
            // are already decided and can no longer be applied for.
            if ($application && $this->isTerminal($application)) {
                continue;
            }

            // Active applications (submitted/under_review/document_verification)
            // are still recommended when the student is eligible, so a
            // scholarship never disappears from recommendations (as it does
            // from Browse) just because an application already exists. The
            // view renders a "View Application" action for these.
            $rec = $this->evaluateRecommendation($profile, $scholarship, $application, $weights);
            $recommendations->push($rec);
        }

        return $this->rankAndOrganize($recommendations);
    }

    /**
     * Evaluate a single scholarship for recommendation.
     *
     * @param  ScholarshipMatchingWeight|null  $weights  Preloaded matching weights,
     *                                                   so batch callers do not re-query them per scholarship.
     */
    public function evaluateRecommendation(
        StudentProfile $profile,
        Scholarship $scholarship,
        ?ScholarshipApplication $application = null,
        ?ScholarshipMatchingWeight $weights = null
    ): ScholarshipRecommendationResult {
        // 1. Evaluate mandatory eligibility (existing engine)
        $eligibilityResult = $this->eligibilityService->evaluate($profile, $scholarship, $application);

        // 2. Calculate matching score (existing engine)
        $matchResult = $this->matchingService->evaluateMatch($profile, $scholarship, $application, $weights);

        // 3. Evaluate requirement readiness
        $readiness = $this->evaluateRequirementReadiness($scholarship, $application);

        // 4. Categorize
        $category = $this->categorize($eligibilityResult, $readiness, $application);

        // 5. Generate explanations
        $reasons = $this->generateExplanations($eligibilityResult, $matchResult, $readiness, $category);

        $appStatus = $application ? $this->rawStatus($application) : null;

        return new ScholarshipRecommendationResult(
            scholarshipId: $scholarship->id,
            scholarshipTitle: $scholarship->title,
            provider: $scholarship->provider,
            scholarshipType: $scholarship->scholarship_type,
            deadline: $scholarship->application_deadline->format('M d, Y'),
            category: $category,
            eligibility: $eligibilityResult->overall,
            matchScore: $matchResult->matchScore,
            rank: null,
            factors: $matchResult->factors,
            reasons: $reasons,
            requirementReadiness: $readiness,
            existingApplicationStatus: $appStatus,
            existingApplicationId: $application?->id,
            eligibilityResult: $eligibilityResult,
        );
    }

    /**
     * Categorize a scholarship recommendation.
     *
     * Decision tree:
     *   1. Failed mandatory eligibility → NOT_ELIGIBLE
     *   2. Draft application with incomplete requirements → POTENTIAL_MATCH
     *   3. Otherwise → ELIGIBLE_RECOMMENDATION
     */
    public function categorize(
        EligibilityEvaluationResult $eligibility,
        array $readiness,
        ?ScholarshipApplication $application
    ): RecommendationCategory {
        if ($eligibility->overall === OverallEligibility::NOT_ELIGIBLE) {
            return RecommendationCategory::NOT_ELIGIBLE;
        }

        // Draft application with incomplete documents → POTENTIAL_MATCH
        if ($application && $this->isDraft($application) && ! $readiness['all_requirements_met']) {
            return RecommendationCategory::POTENTIAL_MATCH;
        }

        return RecommendationCategory::ELIGIBLE_RECOMMENDATION;
    }

    /**
     * Evaluate application requirement readiness.
     */
    public function evaluateRequirementReadiness(
        Scholarship $scholarship,
        ?ScholarshipApplication $application
    ): array {
        $required = $scholarship->requirements->where('is_required', true);
        $total = $required->count();

        if ($total === 0) {
            return [
                'total_required' => 0,
                'uploaded' => 0,
                'verified' => 0,
                'missing' => [],
                'pending_verification' => [],
                'rejected' => [],
                'all_requirements_met' => true,
            ];
        }

        if (! $application) {
            return [
                'total_required' => $total,
                'uploaded' => 0,
                'verified' => 0,
                'missing' => $required->pluck('requirement_name')->toArray(),
                'pending_verification' => [],
                'rejected' => [],
                'all_requirements_met' => false,
            ];
        }

        $application->loadMissing('documents');
        $docs = $application->documents->keyBy('scholarship_requirement_id');

        $missing = [];
        $pending = [];
        $rejected = [];
        $uploaded = 0;
        $verified = 0;

        foreach ($required as $req) {
            $doc = $docs->get($req->id);
            if (! $doc) {
                $missing[] = $req->requirement_name;
            } else {
                $uploaded++;
                if ($doc->verification_status === 'verified') {
                    $verified++;
                } elseif ($doc->verification_status === 'pending') {
                    $pending[] = $req->requirement_name;
                } elseif ($doc->verification_status === 'rejected') {
                    $rejected[] = $req->requirement_name;
                }
            }
        }

        return [
            'total_required' => $total,
            'uploaded' => $uploaded,
            'verified' => $verified,
            'missing' => $missing,
            'pending_verification' => $pending,
            'rejected' => $rejected,
            'all_requirements_met' => empty($missing) && empty($pending) && empty($rejected),
        ];
    }

    /**
     * Generate human-readable explanation reasons.
     */
    protected function generateExplanations(
        EligibilityEvaluationResult $eligibility,
        ScholarshipMatchResult $match,
        array $readiness,
        RecommendationCategory $category
    ): array {
        $reasons = [];

        // NOT_ELIGIBLE: explain failed mandatory criteria
        if ($category === RecommendationCategory::NOT_ELIGIBLE) {
            $reasons[] = 'You do not currently meet all mandatory eligibility requirements for this scholarship.';
            foreach ($eligibility->criteria as $c) {
                if ($c->isMandatory && $c->status->value === 'not_met') {
                    $reasons[] = $c->explanation;
                }
            }

            return $reasons;
        }

        // ELIGIBLE or POTENTIAL: show positive factors first
        foreach ($match->factors as $f) {
            if ($f->isApplicable && $f->status === 'met') {
                $reasons[] = $f->explanation;
            }
        }

        // POTENTIAL_MATCH: explain what needs attention
        if ($category === RecommendationCategory::POTENTIAL_MATCH) {
            foreach ($readiness['missing'] as $doc) {
                $reasons[] = "Required document \"{$doc}\" has not been uploaded.";
            }
            foreach ($readiness['pending_verification'] as $doc) {
                $reasons[] = "Document \"{$doc}\" is awaiting verification.";
            }
            foreach ($readiness['rejected'] as $doc) {
                $reasons[] = "Document \"{$doc}\" was rejected. Please re-upload a valid copy.";
            }
            if (! empty($readiness['missing'])) {
                $reasons[] = 'Upload the missing documents to complete your application.';
            }
        }

        // Show partial/unmet factors
        foreach ($match->factors as $f) {
            if ($f->isApplicable && in_array($f->status, ['not_met', 'partial'])) {
                $reasons[] = $f->explanation;
            }
        }

        if (empty($reasons)) {
            $reasons[] = 'Your profile meets the currently configured eligibility requirements.';
        }

        return $reasons;
    }

    /**
     * Rank eligible recommendations and organize all categories.
     *
     * Ranking: matching score DESC → deadline ASC → title ASC → ID ASC
     * This is deterministic comparison-based sorting, not a greedy algorithm.
     *
     * @return array{eligible: Collection, potential: Collection, not_eligible: Collection}
     */
    protected function rankAndOrganize(Collection $recommendations): array
    {
        $eligible = $recommendations
            ->filter(fn (ScholarshipRecommendationResult $r) => $r->isEligibleRecommendation())
            ->sort(function (ScholarshipRecommendationResult $a, ScholarshipRecommendationResult $b) {
                $score = ($b->matchScore ?? 0) <=> ($a->matchScore ?? 0);
                if ($score !== 0) {
                    return $score;
                }

                // Parse deadline strings to timestamps for chronological comparison
                $deadline = strtotime($a->deadline) <=> strtotime($b->deadline);
                if ($deadline !== 0) {
                    return $deadline;
                }

                $title = strcmp($a->scholarshipTitle, $b->scholarshipTitle);
                if ($title !== 0) {
                    return $title;
                }

                return $a->scholarshipId <=> $b->scholarshipId;
            })
            ->values()
            ->map(fn (ScholarshipRecommendationResult $r, int $i) => new ScholarshipRecommendationResult(
                scholarshipId: $r->scholarshipId,
                scholarshipTitle: $r->scholarshipTitle,
                provider: $r->provider,
                scholarshipType: $r->scholarshipType,
                deadline: $r->deadline,
                category: $r->category,
                eligibility: $r->eligibility,
                matchScore: $r->matchScore,
                rank: $i + 1,
                factors: $r->factors,
                reasons: $r->reasons,
                requirementReadiness: $r->requirementReadiness,
                existingApplicationStatus: $r->existingApplicationStatus,
                existingApplicationId: $r->existingApplicationId,
                eligibilityResult: $r->eligibilityResult,
            ));

        $potential = $recommendations
            ->filter(fn (ScholarshipRecommendationResult $r) => $r->isPotentialMatch())
            ->values();

        $notEligible = $recommendations
            ->filter(fn (ScholarshipRecommendationResult $r) => $r->isNotEligible())
            ->values();

        return [
            'eligible' => $eligible,
            'potential' => $potential,
            'not_eligible' => $notEligible,
        ];
    }

    // ── Status Helpers ────────────────────────────────────────

    protected function rawStatus(ScholarshipApplication $app): string
    {
        $raw = $app->getRawOriginal('status') ?? $app->status;

        return is_object($raw) ? $raw->value : (string) $raw;
    }

    protected function isTerminal(ScholarshipApplication $app): bool
    {
        return in_array($this->rawStatus($app), ['approved', 'rejected'], true);
    }

    protected function isDraft(ScholarshipApplication $app): bool
    {
        return $this->rawStatus($app) === 'draft';
    }
}
