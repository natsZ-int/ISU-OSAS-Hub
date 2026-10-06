<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const ADMIN     = 'admin';
    public const COUNSELOR = 'counselor';
    public const STUDENT   = 'student';

    protected $fillable = ['name', 'label'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}