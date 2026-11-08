<?php

namespace App\Models;

use App\Enums\ScholarshipApplicationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScholarshipApplication extends Model
{
    use HasFactory;

    /**
     * Pre-FSM state kept for backward compatibility with the draft-upload workflow.
     * The FSM formally begins at SUBMITTED.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_DOCUMENT_VERIFICATION = 'document_verification';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_DOCUMENT_VERIFICATION,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'application_number',
        'student_profile_id',
        'scholarship_id',
        'status',
        'submitted_at',
        'remarks',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->orderBy('created_at', 'asc');
    }

    // ── Helpers ────────────────────────────────────────────────

    public function isDraft(): bool
    {
        $raw = $this->getRawOriginal('status') ?? $this->status;
        $statusStr = is_object($raw) ? $raw->value : (string) $raw;

        return $statusStr === self::STATUS_DRAFT;
    }

    public function allowsDocumentUpload(): bool
    {
        return $this->isDraft();
    }

    public function hasAllRequiredDocuments(): bool
    {
        $requiredIds = $this->scholarship->requiredRequirements->pluck('id');

        if ($requiredIds->isEmpty()) {
            return true;
        }

        $uploadedRequirementIds = $this->documents()
            ->whereNotNull('scholarship_requirement_id')
            ->pluck('scholarship_requirement_id');

        return $requiredIds->diff($uploadedRequirementIds)->isEmpty();
    }

    public function fsmStatus(): ?ScholarshipApplicationStatus
    {
        if ($this->isDraft()) {
            return null;
        }

        $raw = $this->getRawOriginal('status') ?? $this->status;
        if ($raw instanceof ScholarshipApplicationStatus) {
            return $raw;
        }

        return is_string($raw) ? ScholarshipApplicationStatus::tryFrom($raw) : null;
    }

    // ── Scopes ─────────────────────────────────────────────────

    public function scopeForStudent(Builder $query, int $studentProfileId): Builder
    {
        return $query->where('student_profile_id', $studentProfileId);
    }
}
