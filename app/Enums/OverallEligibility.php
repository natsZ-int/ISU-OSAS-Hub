<?php

namespace App\Enums;

enum OverallEligibility: string
{
    case ELIGIBLE     = 'eligible';
    case NOT_ELIGIBLE = 'not_eligible';

    public function label(): string
    {
        return match ($this) {
            self::ELIGIBLE     => 'Eligible',
            self::NOT_ELIGIBLE => 'Not Eligible',
        };
    }
}