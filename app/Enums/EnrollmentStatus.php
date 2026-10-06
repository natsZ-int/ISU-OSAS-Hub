<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case ENROLLED     = 'enrolled';
    case NOT_ENROLLED = 'not_enrolled';
    case UNKNOWN      = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::ENROLLED     => 'Enrolled',
            self::NOT_ENROLLED => 'Not Enrolled',
            self::UNKNOWN      => 'Unknown',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}