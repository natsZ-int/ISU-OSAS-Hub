<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationDocument extends Model
{
    use HasFactory;

    public const VERIFICATION_PENDING = 'pending';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_REJECTED = 'rejected';

    protected $fillable = [
        'scholarship_application_id',
        'scholarship_requirement_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'verification_status',
        'verification_remarks',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ScholarshipApplication::class, 'scholarship_application_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ScholarshipRequirement::class, 'scholarship_requirement_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
