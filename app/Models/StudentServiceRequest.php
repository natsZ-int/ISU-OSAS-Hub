<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentServiceRequest extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_REQUIRES_INFORMATION = 'requires_information';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'request_number',
        'student_profile_id',
        'service_type_id',
        'description',
        'status',
        'submitted_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'remarks',
        'internal_remarks',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ServiceRequestDocument::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(StudentServiceRequestHistory::class)->orderBy('created_at', 'desc');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }
}
