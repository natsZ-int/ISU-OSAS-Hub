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
        'data',
        'read_at',
        'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'read_at'    => 'datetime',
            'emailed_at' => 'datetime',
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

    // ── Email / metadata helpers ───────────────────────────

    /**
     * Read a single non-confidential metadata value stored with the notification.
     */
    public function meta(string $key, mixed $default = null): mixed
    {
        $data = $this->data;

        return is_array($data) ? ($data[$key] ?? $default) : $default;
    }

    public function reference(): ?string
    {
        $value = $this->meta('reference');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function referenceLabel(): ?string
    {
        $value = $this->meta('reference_label');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function statusLabel(): ?string
    {
        $value = $this->meta('status');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function nextStep(): ?string
    {
        $value = $this->meta('next_step');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * An authorized staff explanation (e.g. rejection reason), when one exists.
     */
    public function staffExplanation(): ?string
    {
        $value = $this->meta('reason');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Prefer the URL captured when the notification was created (safe, generated
     * server-side), falling back to the role-aware routing helper.
     */
    public function getDetailUrl(): ?string
    {
        $url = $this->meta('url');

        if (is_string($url) && $url !== '') {
            return $url;
        }

        return $this->getActionUrl();
    }

    /**
     * Get a safe action URL based on the related record and the viewing user's role.
     *
     * Staff recipients must not be sent to student-only routes (which would 403).
     */
    public function getActionUrl(): ?string
    {
        $user = auth()->user();
        $isCounselor = $user !== null && method_exists($user, 'isCounselor') && $user->isCounselor();

        try {
            return match ($this->related_type) {
                'ScholarshipApplication' => $isCounselor
                    ? null
                    : route('student.applications.show', $this->related_id),
                'GuidanceAppointment'    => $isCounselor
                    ? route('counselor.guidance.appointments.show', $this->related_id)
                    : route('student.guidance.appointments.show', $this->related_id),
                'StudentServiceRequest'  => $isCounselor
                    ? null
                    : route('student.services.show', $this->related_id),
                default                  => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }
}