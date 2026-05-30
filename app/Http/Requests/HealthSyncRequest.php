<?php

namespace App\Http\Requests;

use App\Models\HealthMetric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HealthSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'records' => ['sometimes', 'array'],
            'steps' => ['sometimes', 'array'],
            'sleep_sessions' => ['sometimes', 'array'],
            'heart_rates' => ['sometimes', 'array'],
            'calories_burned' => ['sometimes', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasPayload = collect([
                'records',
                'steps',
                'sleep_sessions',
                'heart_rates',
                'calories_burned',
            ])->contains(fn (string $key): bool => filled($this->input($key)));

            if (! $hasPayload) {
                $validator->errors()->add(
                    'records',
                    'Provide at least one health record array: records, steps, sleep_sessions, heart_rates, or calories_burned.',
                );
            }
        });
    }

    public function normalizedRecords(): array
    {
        $records = collect($this->input('records', []))
            ->map(fn (array $record): array => $this->normalizeGenericRecord($record));

        $records = $records
            ->merge($this->normalizeSteps())
            ->merge($this->normalizeSleepSessions())
            ->merge($this->normalizeHeartRates())
            ->merge($this->normalizeCalories());

        $payload = ['records' => $records->values()->all()];

        Validator::make($payload, [
            'records' => ['required', 'array', 'min:1'],
            'records.*.metric_type' => ['required', 'string', Rule::in(HealthMetric::allowedTypes())],
            'records.*.value' => ['required', 'array'],
            'records.*.start_time' => ['required', 'date'],
            'records.*.end_time' => ['nullable', 'date'],
            'records.*.source_app' => ['required', 'string', 'max:255'],
            'records.*.external_id' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return $payload['records'];
    }

    private function normalizeGenericRecord(array $record): array
    {
        return [
            'external_id' => $record['external_id'] ?? $record['source_record_id'] ?? null,
            'metric_type' => $record['metric_type'] ?? null,
            'value' => $record['value'] ?? [],
            'start_time' => $record['start_time'] ?? null,
            'end_time' => $record['end_time'] ?? null,
            'source_app' => $record['source_app'] ?? 'com.sec.android.app.shealth',
        ];
    }

    private function normalizeSteps(): Collection
    {
        return collect($this->input('steps', []))->map(fn (array $record): array => [
            'external_id' => $record['external_id'] ?? null,
            'metric_type' => HealthMetric::STEPS,
            'value' => [
                'count' => (int) ($record['count'] ?? $record['steps'] ?? 0),
            ],
            'start_time' => $record['start_time'] ?? null,
            'end_time' => $record['end_time'] ?? null,
            'source_app' => $record['source_app'] ?? 'com.sec.android.app.shealth',
        ]);
    }

    private function normalizeSleepSessions(): Collection
    {
        return collect($this->input('sleep_sessions', []))->map(function (array $record): array {
            $duration = $record['duration_minutes'] ?? null;

            if (! $duration && filled($record['start_time'] ?? null) && filled($record['end_time'] ?? null)) {
                $duration = Carbon::parse($record['start_time'])->diffInMinutes(Carbon::parse($record['end_time']));
            }

            return [
                'external_id' => $record['external_id'] ?? null,
                'metric_type' => HealthMetric::SLEEP_SESSION,
                'value' => [
                    'duration_minutes' => (int) $duration,
                    'stages' => $record['stages'] ?? [],
                    'title' => $record['title'] ?? null,
                ],
                'start_time' => $record['start_time'] ?? null,
                'end_time' => $record['end_time'] ?? null,
                'source_app' => $record['source_app'] ?? 'com.sec.android.app.shealth',
            ];
        });
    }

    private function normalizeHeartRates(): Collection
    {
        return collect($this->input('heart_rates', []))->map(function (array $record): array {
            $samples = collect($record['samples'] ?? []);

            if ($samples->isEmpty() && isset($record['bpm'])) {
                $samples = collect([[
                    'bpm' => (int) $record['bpm'],
                    'time' => $record['start_time'] ?? now()->toIso8601String(),
                ]]);
            }

            return [
                'external_id' => $record['external_id'] ?? null,
                'metric_type' => HealthMetric::HEART_RATE,
                'value' => [
                    'samples' => $samples->values()->all(),
                    'bpm_avg' => $record['bpm_avg'] ?? $samples->avg('bpm'),
                    'bpm_min' => $record['bpm_min'] ?? $samples->min('bpm'),
                    'bpm_max' => $record['bpm_max'] ?? $samples->max('bpm'),
                ],
                'start_time' => $record['start_time'] ?? null,
                'end_time' => $record['end_time'] ?? null,
                'source_app' => $record['source_app'] ?? 'com.sec.android.app.shealth',
            ];
        });
    }

    private function normalizeCalories(): Collection
    {
        return collect($this->input('calories_burned', []))->map(fn (array $record): array => [
            'external_id' => $record['external_id'] ?? null,
            'metric_type' => HealthMetric::CALORIES_BURNED,
            'value' => [
                'kilocalories' => (float) ($record['kilocalories'] ?? $record['calories'] ?? 0),
            ],
            'start_time' => $record['start_time'] ?? null,
            'end_time' => $record['end_time'] ?? null,
            'source_app' => $record['source_app'] ?? 'com.sec.android.app.shealth',
        ]);
    }
}

