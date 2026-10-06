<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_type',
        'related_id',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at'    => 'datetime',
            'related_id' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    // ── Helpers ────────────────────────────────────────────

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Get the category label for display.
     */
    public function getCategoryLabel(): string
    {
        return match (true) {
            str_starts_with($this->type, 'scholarship.')       => 'Scholarship',
            str_starts_with($this->type, 'guidance.')          => 'Guidance',
            str_starts_with($this->type, 'student_services.')  => 'Student Services',
            default                                            => 'General',
        };
    }

    /**
     * Get a safe action URL based on the related record.
     */
    public function getActionUrl(): ?string
    {
        try {
            return match ($this->related_type) {
                'ScholarshipApplication' => route('student.applications.show', $this->related_id),
                'GuidanceAppointment'    => route('student.guidance.appointments.show', $this->related_id),
                'StudentServiceRequest'  => route('student.services.show', $this->related_id),
                default                  => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}