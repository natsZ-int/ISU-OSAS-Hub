<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_service_request_id',
        'service_type_requirement_id',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(StudentServiceRequest::class, 'student_service_request_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ServiceTypeRequirement::class, 'service_type_requirement_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}