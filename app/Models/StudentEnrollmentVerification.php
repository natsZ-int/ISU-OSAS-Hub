<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentEnrollmentVerification extends Model
{
    protected $fillable = [
        'student_profile_id',
        'academic_year',
        'semester',
        'status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function historyForProfile(): HasMany
    {
        return $this->hasMany(StudentEnrollmentVerification::class, 'student_profile_id', 'student_profile_id');
    }
}
