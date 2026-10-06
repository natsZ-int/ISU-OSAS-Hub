<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'contact_number',
        'college',
        'program',
        'year_level',
        'enrollment_status',
        'gwa',
        'family_income',
        'address',
    ];

    protected $casts = [
        'gwa'           => 'decimal:2',
        'family_income' => 'decimal:2',
        'year_level'    => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scholarshipApplications(): HasMany
    {
        return $this->hasMany(ScholarshipApplication::class);
    }

    public function guidanceAppointments(): HasMany
    {
        return $this->hasMany(GuidanceAppointment::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(StudentServiceRequest::class);
    }
}