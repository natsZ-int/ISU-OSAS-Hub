<?php

namespace App\DataObjects;

use App\Enums\OverallEligibility;

class ScholarshipMatchResult
{
    /**
     * @param  FactorResult[]  $factors
     */
    public function __construct(
        public readonly int $scholarshipId,
        public readonly string $scholarshipTitle,
        public readonly string $provider,
        public readonly string $scholarshipType,
        public readonly string $deadline,
        public readonly string $eligibilityLabel,
        public readonly OverallEligibility $eligibility,
        public readonly ?float $matchScore,
        public readonly ?int $rank,
        public readonly array $factors,
        public readonly array $reasons,
        public readonly ?string $existingApplicationStatus,
        public readonly ?int $existingApplicationId,
        public readonly ?EligibilityEvaluationResult $eligibilityResult,
    ) {}

    public function isEligible(): bool
    {
        return $this->eligibility === OverallEligibility::ELIGIBLE;
    }

    public function hasExistingTerminalApplication(): bool
    {
        return in_array($this->existingApplicationStatus, ['approved', 'rejected'], true);
    }

    public function hasExistingActiveApplication(): bool
    {
        return $this->existingApplicationStatus !== null
            && ! $this->hasExistingTerminalApplication();
    }
}