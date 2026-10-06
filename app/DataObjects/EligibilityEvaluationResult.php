<?php

namespace App\DataObjects;

use App\Enums\OverallEligibility;
use Carbon\Carbon;

class EligibilityEvaluationResult
{
    /**
     * @param  CriterionResult[]  $criteria
     */
    public function __construct(
        public readonly int $studentProfileId,
        public readonly int $scholarshipId,
        public readonly OverallEligibility $overall,
        public readonly array $criteria,
        public readonly ?string $reasonSummary,
        public readonly Carbon $evaluatedAt,
        public readonly array $applicationReadiness = [],
    ) {}

    public function isEligible(): bool
    {
        return $this->overall === OverallEligibility::ELIGIBLE;
    }
}