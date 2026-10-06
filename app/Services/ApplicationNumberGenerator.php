<?php

namespace App\Services;

use App\Models\ScholarshipApplication;
use Illuminate\Support\Str;

class ApplicationNumberGenerator
{
    /**
     * Generate a unique application number in format: SCH-YYYY-NNNNNN
     */
    public static function generate(): string
    {
        $year = now()->format('Y');
        $prefix = "SCH-{$year}-";

        // Get the latest application number for this year
        $latest = ScholarshipApplication::where('application_number', 'like', "{$prefix}%")
            ->orderBy('application_number', 'desc')
            ->value('application_number');

        if ($latest) {
            $lastNumber = (int) Str::after($latest, $prefix);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $number = $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Safety check for concurrent requests
        if (ScholarshipApplication::where('application_number', $number)->exists()) {
            return self::generate(); // Retry with incremented number
        }

        return $number;
    }
}