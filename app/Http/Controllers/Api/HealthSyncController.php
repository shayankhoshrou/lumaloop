<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HealthSyncRequest;
use App\Models\HealthMetric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HealthSyncController extends Controller
{
    public function store(HealthSyncRequest $request): JsonResponse
    {
        abort_unless(
            $request->user()->tokenCan('health:sync') || $request->user()->tokenCan('*'),
            403,
            'This token cannot sync health data.',
        );

        $user = $request->user();
        $records = collect($request->normalizedRecords())
            ->map(function (array $record) use ($user): array {
                $startTime = Carbon::parse($record['start_time'])->utc();
                $endTime = filled($record['end_time'] ?? null)
                    ? Carbon::parse($record['end_time'])->utc()
                    : null;

                $syncKey = HealthMetric::makeSyncKey(
                    userId: $user->id,
                    metricType: $record['metric_type'],
                    sourceApp: $record['source_app'],
                    startTime: $startTime,
                    endTime: $endTime,
                    sourceRecordId: $record['external_id'] ?? null,
                );

                return [
                    'user_id' => $user->id,
                    'metric_type' => $record['metric_type'],
                    'value' => json_encode($record['value'], JSON_THROW_ON_ERROR),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'source_app' => $record['source_app'],
                    'source_record_id' => $record['external_id'] ?? null,
                    'sync_key' => $syncKey,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values();

        HealthMetric::query()->upsert(
            $records->all(),
            uniqueBy: ['user_id', 'sync_key'],
            update: [
                'value',
                'start_time',
                'end_time',
                'source_app',
                'source_record_id',
                'updated_at',
            ],
        );

        return response()->json([
            'status' => 'ok',
            'synced_records' => $records->count(),
            'metric_types' => $records->pluck('metric_type')->unique()->values(),
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $day = Carbon::parse($request->query('date', now()->toDateString()));
        $start = $day->copy()->startOfDay()->utc();
        $end = $day->copy()->endOfDay()->utc();

        $steps = HealthMetric::query()
            ->forUser($user)
            ->ofType(HealthMetric::STEPS)
            ->between($start, $end)
            ->get()
            ->sum(fn (HealthMetric $metric): int => (int) data_get($metric->value, 'count', 0));

        $sleepMinutes = HealthMetric::query()
            ->forUser($user)
            ->ofType(HealthMetric::SLEEP_SESSION)
            ->between($start->copy()->subHours(12), $end)
            ->get()
            ->sum(fn (HealthMetric $metric): int => (int) (
                data_get($metric->value, 'duration_minutes')
                ?: $metric->start_time->diffInMinutes($metric->end_time)
            ));

        return response()->json([
            'date' => $day->toDateString(),
            'steps' => $steps,
            'sleep_minutes' => $sleepMinutes,
        ]);
    }
}

