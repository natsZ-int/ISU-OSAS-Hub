<?php

namespace App\Enums;

enum RecommendationCategory: string
{
    case ELIGIBLE_RECOMMENDATION = 'eligible_recommendation';
    case POTENTIAL_MATCH         = 'potential_match';
    case NOT_ELIGIBLE            = 'not_eligible';

    public function label(): string
    {
        return match ($this) {
            self::ELIGIBLE_RECOMMENDATION => 'Eligible Recommendation',
            self::POTENTIAL_MATCH         => 'Potential Match',
            self::NOT_ELIGIBLE            => 'Not Eligible',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ELIGIBLE_RECOMMENDATION => '#16a34a',
            self::POTENTIAL_MATCH         => '#d97706',
            self::NOT_ELIGIBLE            => '#dc2626',
        };
    }

    public function bgColor(): string
    {
        return match ($this) {
            self::ELIGIBLE_RECOMMENDATION => '#dcfce7',
            self::POTENTIAL_MATCH         => '#fef3c7',
            self::NOT_ELIGIBLE            => '#fee2e2',
        };
    }
}