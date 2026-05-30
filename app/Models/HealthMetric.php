<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class HealthMetric extends Model
{
    use HasFactory;

    public const STEPS = 'steps';
    public const HEART_RATE = 'heart_rate';
    public const SLEEP_SESSION = 'sleep_session';
    public const CALORIES_BURNED = 'calories_burned';

    protected $fillable = [
        'user_id',
        'metric_type',
        'value',
        'start_time',
        'end_time',
        'source_app',
        'source_record_id',
        'sync_key',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'start_time' => 'immutable_datetime',
            'end_time' => 'immutable_datetime',
        ];
    }

    public static function allowedTypes(): array
    {
        return [
            self::STEPS,
            self::HEART_RATE,
            self::SLEEP_SESSION,
            self::CALORIES_BURNED,
        ];
    }

    public static function makeSyncKey(
        int $userId,
        string $metricType,
        string $sourceApp,
        Carbon|string $startTime,
        Carbon|string|null $endTime,
        ?string $sourceRecordId = null,
    ): string {
        $stableIdentity = $sourceRecordId ?: implode('|', [
            Carbon::parse($startTime)->toIso8601String(),
            $endTime ? Carbon::parse($endTime)->toIso8601String() : 'open-ended',
        ]);

        return hash('sha256', implode('|', [
            $userId,
            $sourceApp,
            $metricType,
            $stableIdentity,
        ]));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->id : $user);
    }

    public function scopeOfType(Builder $query, string $metricType): Builder
    {
        return $query->where('metric_type', $metricType);
    }

    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query
            ->where('start_time', '<', $end)
            ->where(function (Builder $nested) use ($start): void {
                $nested
                    ->whereNull('end_time')
                    ->orWhere('end_time', '>=', $start);
            });
    }

    public function numericValue(string $key, float|int $default = 0): float|int
    {
        return data_get($this->value, $key, $default);
    }
}

