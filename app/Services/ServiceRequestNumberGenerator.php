<?php

namespace App\Services;

use App\Models\StudentServiceRequest;
use Illuminate\Support\Facades\DB;

class ServiceRequestNumberGenerator
{
    /**
     * Generates a unique request number format: SR-YYYY-000001
     */
    public function generate(): string
    {
        $year = date('Y');
        $prefix = "SR-{$year}-";

        return DB::transaction(function () use ($year, $prefix) {
            $latest = StudentServiceRequest::where('request_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('request_number');

            if ($latest) {
                $lastSequence = (int) substr($latest, strlen($prefix));
                $nextSequence = $lastSequence + 1;
            } else {
                $nextSequence = 1;
            }

            return $prefix . str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
        });
    }
}