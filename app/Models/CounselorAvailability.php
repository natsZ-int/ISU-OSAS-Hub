<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CounselorAvailability extends Model
{
    use HasFactory;

    public const DAYS = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    protected $fillable = [
        'counselor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_available',
    ];

    protected $casts = [
        'day_of_week'  => 'integer',
        'is_available' => 'boolean',
    ];

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopeForDay(Builder $query, int $dayOfWeek): Builder
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function overlapsWith(string $startTime, string $endTime): bool
    {
        return $this->start_time < $endTime && $this->end_time > $startTime;
    }

    public function getDayName(): string
    {
        return self::DAYS[$this->day_of_week] ?? 'Unknown';
    }
}