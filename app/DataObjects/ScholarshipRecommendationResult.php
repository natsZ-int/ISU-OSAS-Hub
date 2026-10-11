<?php

namespace App\DataObjects;

use App\Enums\OverallEligibility;
use App\Enums\RecommendationCategory;

class ScholarshipRecommendationResult
{
    /**
     * @param  FactorResult[]  $factors
     * @param  string[]        $reasons
     * @param  array           $requirementReadiness
     */
    public function __construct(
        public readonly int $scholarshipId,
        public readonly string $scholarshipTitle,
        public readonly string $provider,
        public readonly string $scholarshipType,
        public readonly string $deadline,
        public readonly RecommendationCategory $category,
        public readonly OverallEligibility $eligibility,
        public readonly ?float $matchScore,
        public readonly ?int $rank,
        public readonly array $factors,
        public readonly array $reasons,
        public readonly array $requirementReadiness,
        public readonly ?string $existingApplicationStatus,
        public readonly ?int $existingApplicationId,
        public readonly ?EligibilityEvaluationResult $eligibilityResult,
    ) {}

    public function isEligibleRecommendation(): bool
    {
        return $this->category === RecommendationCategory::ELIGIBLE_RECOMMENDATION;
    }

    public function isPotentialMatch(): bool
    {
        return $this->category === RecommendationCategory::POTENTIAL_MATCH;
    }

    public function isNotEligible(): bool
    {
        return $this->category === RecommendationCategory::NOT_ELIGIBLE;
    }

    public function hasExistingActiveApplication(): bool
    {
        return $this->existingApplicationStatus !== null
            && ! in_array($this->existingApplicationStatus, ['approved', 'rejected'], true);
    }
}