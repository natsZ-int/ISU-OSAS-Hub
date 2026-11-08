<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'instructions',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ServiceTypeRequirement::class);
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(StudentServiceRequest::class);
    }
}
