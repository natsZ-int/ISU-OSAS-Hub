<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentServiceRequestHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'student_service_request_id',
        'previous_status',
        'new_status',
        'changed_by',
        'remarks',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(StudentServiceRequest::class, 'student_service_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
