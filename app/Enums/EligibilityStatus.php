<?php

namespace App\Enums;

enum EligibilityStatus: string
{
    case MET = 'met';
    case NOT_MET = 'not_met';
    case NOT_APPLICABLE = 'not_applicable';

    public function label(): string
    {
        return match ($this) {
            self::MET => 'Met',
            self::NOT_MET => 'Not Met',
            self::NOT_APPLICABLE => 'Not Applicable',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MET => '✓',
            self::NOT_MET => '✗',
            self::NOT_APPLICABLE => '—',
        };
    }
}
