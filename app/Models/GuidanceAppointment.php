<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class GuidanceAppointment extends Model
{
    use HasFactory;

    // Status constants
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED  = 'rejected';

    // Statuses that block time slots
    public const BLOCKING_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_CONFIRMED,
    ];

    // Terminal statuses (no transitions allowed)
    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'student_profile_id',
        'counselor_id',
        'appointment_type_id',
        'appointment_date',
        'start_time',
        'end_time',
        'appointment_type',
        'reason',
        'status',
        'rejection_reason',
        'counselor_notes',
        'student_notes',
        'cancelled_at',
        'cancelled_by',
        'reminder_sent_at',
    ];

    protected $casts = [
        'appointment_date'  => 'date',
        'cancelled_at'      => 'datetime',
        'reminder_sent_at'  => 'datetime',
    ];

    // ── Relationships ───────────────────────────────

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function counselor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counselor_id');
    }

    public function appointmentType(): BelongsTo
    {
        return $this->belongsTo(GuidanceAppointmentType::class, 'appointment_type_id');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ── Scopes ──────────────────────────────────────

    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', self::BLOCKING_STATUSES);
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeCounselorBlockingOnDate(Builder $query, int $counselorId, string $date): Builder
    {
        return $query->where('counselor_id', $counselorId)
                     ->onDate($date)
                     ->blocking();
    }

    public function scopeStudentBlockingOnDate(Builder $query, int $studentProfileId, string $date): Builder
    {
        return $query->where('student_profile_id', $studentProfileId)
                     ->onDate($date)
                     ->blocking();
    }

    // ── Helpers ─────────────────────────────────────

    public function isBlocking(): bool
    {
        return in_array($this->status, self::BLOCKING_STATUSES, true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function getDisplayStatus(): string
    {
        return ucfirst($this->status);
    }
}