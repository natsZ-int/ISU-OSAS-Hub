<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ── Role ──────────────────────────────────────────

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    public function isCounselor(): bool
    {
        return $this->hasRole(Role::COUNSELOR);
    }

    public function isStudent(): bool
    {
        return $this->hasRole(Role::STUDENT);
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    // ── Student Profile ───────────────────────────────

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    // ── Scholarships ──────────────────────────────────

    public function createdScholarships(): HasMany
    {
        return $this->hasMany(Scholarship::class, 'created_by');
    }

    // ── Guidance ──────────────────────────────────────

    public function counselorAppointments(): HasMany
    {
        return $this->hasMany(GuidanceAppointment::class, 'counselor_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(CounselorAvailability::class, 'counselor_id');
    }

    // ── Service Requests ──────────────────────────────

    public function handledServiceRequests(): HasMany
    {
        return $this->hasMany(StudentServiceRequest::class, 'handled_by');
    }

    // ── Notifications & Audit ─────────────────────────

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Document Verification ─────────────────────────

    public function verifiedDocuments(): HasMany
    {
        return $this->hasMany(ApplicationDocument::class, 'verified_by');
    }

    // ── Status History ────────────────────────────────

    public function changedStatusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class, 'changed_by');
    }
}