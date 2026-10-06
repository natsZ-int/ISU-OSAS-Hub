<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class GuidanceAppointmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'is_active'        => 'boolean',
        'display_order'    => 'integer',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(GuidanceAppointment::class, 'appointment_type_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('display_order');
    }

    // Check if this type is used by any appointments
    public function isInUse(): bool
    {
        return $this->appointments()->exists();
    }
}