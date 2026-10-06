<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuidanceSchedulingSettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_start_time',
        'office_end_time',
        'default_appointment_duration_minutes',
        'minimum_booking_notice_minutes',
        'student_cancellation_notice_minutes',
        'maximum_future_booking_days',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'default_appointment_duration_minutes'  => 'integer',
        'minimum_booking_notice_minutes'        => 'integer',
        'student_cancellation_notice_minutes'   => 'integer',
        'maximum_future_booking_days'           => 'integer',
        'is_active'                             => 'boolean',
    ];

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Get the single active settings record, or create defaults
    public static function getActive(): self
    {
        return self::firstOrCreate(
            ['is_active' => true],
            [
                'office_start_time'                  => '08:00:00',
                'office_end_time'                    => '17:00:00',
                'default_appointment_duration_minutes' => 30,
                'minimum_booking_notice_minutes'       => 60,
                'student_cancellation_notice_minutes'  => 60,
                'maximum_future_booking_days'          => 30,
            ]
        );
    }
}