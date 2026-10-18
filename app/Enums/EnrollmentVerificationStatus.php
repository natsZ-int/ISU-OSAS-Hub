<?php

namespace App\Enums;

enum EnrollmentVerificationStatus: string
{
    case PENDING           = 'pending';
    case VERIFIED          = 'verified';
    case REJECTED          = 'rejected';
    case UNABLE_TO_VERIFY  = 'unable_to_verify';

    public function label(): string
    {
        return match ($this) {
            self::PENDING          => 'Pending',
            self::VERIFIED         => 'Verified',
            self::REJECTED         => 'Rejected',
            self::UNABLE_TO_VERIFY => 'Unable to Verify',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
