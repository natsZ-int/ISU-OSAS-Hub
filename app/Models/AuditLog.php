<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_type',
        'record_id',
        'description',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'record_id' => 'integer',
        ];
    }

    // ── Relationships ──────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeForRecordType(Builder $query, string $recordType): Builder
    {
        return $query->where('record_type', $recordType);
    }

    public function scopeDateFrom(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '>=', $date.' 00:00:00');
    }

    public function scopeDateTo(Builder $query, string $date): Builder
    {
        return $query->where('created_at', '<=', $date.' 23:59:59');
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim(preg_replace('/\s+/', ' ', $term));
        $term = mb_substr($term, 0, 200);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('description', 'like', "%{$term}%")
                ->orWhere('action', 'like', "%{$term}%")
                ->orWhere('module', 'like', "%{$term}%")
                ->orWhere('record_type', 'like', "%{$term}%")
                ->orWhere('record_id', $term)
                ->orWhereHas('user', function (Builder $uq) use ($term) {
                    $uq->where(function (Builder $nameOrEmail) use ($term) {
                        $nameOrEmail->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
                });
        });
    }

    // ── Helpers ────────────────────────────────────────────

    /**
     * Get distinct module values for filter dropdowns.
     */
    public static function distinctModules(): array
    {
        return static::distinct()->orderBy('module')->pluck('module')->filter()->values()->toArray();
    }

    /**
     * Get distinct action values for filter dropdowns.
     */
    public static function distinctActions(): array
    {
        return static::distinct()->orderBy('action')->pluck('action')->filter()->values()->toArray();
    }

    /**
     * Get distinct record_type values for filter dropdowns.
     */
    public static function distinctRecordTypes(): array
    {
        return static::distinct()->orderBy('record_type')->pluck('record_type')->filter()->values()->toArray();
    }
}
