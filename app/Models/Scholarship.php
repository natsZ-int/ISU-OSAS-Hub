<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Scholarship extends Model
{
    use HasFactory;

    public const STATUS_DRAFT    = 'draft';
    public const STATUS_OPEN     = 'open';
    public const STATUS_CLOSED   = 'closed';
    public const STATUS_ARCHIVED = 'archived';

    public const ALLOWED_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_CLOSED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'title',
        'description',
        'provider',
        'scholarship_type',
        'minimum_gwa',
        'maximum_family_income',
        'eligible_programs',
        'eligible_year_levels',
        'required_enrollment_status',
        'application_start_date',
        'application_deadline',
        'status',
        'created_by',
    ];

    protected $casts = [
        'minimum_gwa'            => 'decimal:2',
        'maximum_family_income'  => 'decimal:2',
        'eligible_programs'      => 'array',
        'eligible_year_levels'   => 'array',
        'application_start_date' => 'date',
        'application_deadline'   => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ScholarshipRequirement::class)->orderBy('display_order');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN)
                     ->where('application_start_date', '<=', now()->toDateString())
                     ->where('application_deadline', '>=', now()->toDateString());
    }

    public function scopeAvailableForStudents(Builder $query): Builder
    {
        return $query->open();
    }

    public function isOpenForApplication(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->application_start_date->lte(now())
            && $this->application_deadline->gte(now());
    }

    public function requiredRequirements(): HasMany
    {
        return $this->requirements()->where('is_required', true);
    }
}