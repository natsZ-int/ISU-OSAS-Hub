<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceTypeRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_type_id',
        'name',
        'description',
        'is_required',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required'   => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ServiceRequestDocument::class);
    }
}