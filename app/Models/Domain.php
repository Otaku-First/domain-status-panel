<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Domain extends Model
{
    use HasFactory;
    protected $fillable = [
        'hostname',
        'created_by',
        'interval',
        'timeout',
        'method',
        'body',
        'is_active',
        'last_checked_at',
    ];

    protected $casts = [
        'body' => 'array',
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    // Relationships

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function checks(): HasMany
    {
        return $this->hasMany(DomainCheck::class);
    }

    public function latestCheck(): HasOne
    {
        return $this->hasOne(DomainCheck::class)->latestOfMany('checked_at');
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeNeedsCheck(Builder $query): Builder
    {
        return $query->active()
            ->where(function (Builder $q) {
                $q->whereNull('last_checked_at')
                    ->orWhereRaw('`last_checked_at` <= NOW() - INTERVAL `interval` SECOND');
            });
    }

    // Helpers

    public function markAsChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }

    /**
     * Calculate uptime percentage for a given period.
     */
    public function getUptimePercentage(int $hours = 24): ?float
    {
        $since = now()->subHours($hours);

        $stats = $this->checks()
            ->where('checked_at', '>=', $since)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN result = "SUCCESS" THEN 1 ELSE 0 END) as successful')
            ->first();

        if (!$stats || $stats->total === 0) {
            return null;
        }

        return round(($stats->successful / $stats->total) * 100, 2);
    }

    /**
     * Calculate average response time for a given period.
     */
    public function getAvgResponseTime(int $hours = 24): ?float
    {
        $since = now()->subHours($hours);

        $avg = $this->checks()
            ->where('checked_at', '>=', $since)
            ->whereNotNull('response_time_ms')
            ->avg('response_time_ms');

        return $avg ? round($avg, 0) : null;
    }

    /**
     * Check if domain is currently down (last check failed).
     */
    public function isCurrentlyDown(): bool
    {
        $latestCheck = $this->relationLoaded('latestCheck')
            ? $this->latestCheck
            : $this->latestCheck()->first();

        if (!$latestCheck) {
            return false;
        }

        return !$latestCheck->result->isSuccessful();
    }
}
