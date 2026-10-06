<?php

namespace App\DataObjects;

class FactorResult
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly float $configuredWeight,
        public readonly bool $isApplicable,
        public readonly ?float $normalizedScore,
        public readonly ?float $weightedContribution,
        public readonly string $status,
        public readonly string $explanation,
    ) {}
}