<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipMatchingWeight extends Model
{
    protected $fillable = [
        'academic_weight',
        'financial_weight',
        'program_weight',
        'year_level_weight',
        'document_readiness_weight',
        'is_active',
        'updated_by',
    ];

    protected $casts = [
        'academic_weight' => 'decimal:2',
        'financial_weight' => 'decimal:2',
        'program_weight' => 'decimal:2',
        'year_level_weight' => 'decimal:2',
        'document_readiness_weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function active(): ?self
    {
        return static::where('is_active', true)->latest()->first();
    }

    /**
     * Configured defaults used when no administrator-defined weights exist.
     *
     * Keeping a deterministic fallback means scholarship matching and
     * recommendations continue to work on a freshly installed database,
     * instead of silently returning an empty result set.
     */
    public static function defaults(): self
    {
        return new self([
            'academic_weight' => 30,
            'financial_weight' => 25,
            'program_weight' => 20,
            'year_level_weight' => 15,
            'document_readiness_weight' => 10,
            'is_active' => true,
        ]);
    }

    public static function activeOrDefault(): self
    {
        return static::active() ?? static::defaults();
    }

    public function total(): float
    {
        return (float) $this->academic_weight
             + (float) $this->financial_weight
             + (float) $this->program_weight
             + (float) $this->year_level_weight
             + (float) $this->document_readiness_weight;
    }

    public function toDecimalArray(): array
    {
        return [
            'academic' => (float) $this->academic_weight / 100,
            'financial' => (float) $this->financial_weight / 100,
            'program' => (float) $this->program_weight / 100,
            'year_level' => (float) $this->year_level_weight / 100,
            'document_readiness' => (float) $this->document_readiness_weight / 100,
        ];
    }
}
