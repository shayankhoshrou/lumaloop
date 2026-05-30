<?php

namespace App\Http\Controllers;

use App\Models\Habit;
use App\Models\HealthMetric;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $timezone = config('app.timezone', 'UTC');
        $selectedDay = Carbon::parse($request->query('date', now($timezone)->toDateString()), $timezone);
        $start = $selectedDay->copy()->startOfDay()->utc();
        $end = $selectedDay->copy()->endOfDay()->utc();

        $dailySteps = HealthMetric::query()
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

        $latestHeartRate = HealthMetric::query()
            ->forUser($user)
            ->ofType(HealthMetric::HEART_RATE)
            ->latest('start_time')
            ->first();

        $habits = Habit::query()
            ->whereBelongsTo($user)
            ->active()
            ->with(['logs' => fn ($query) => $query->whereDate('log_date', $selectedDay->toDateString())])
            ->orderBy('name')
            ->get();

        $recentMetrics = HealthMetric::query()
            ->forUser($user)
            ->latest('start_time')
            ->limit(8)
            ->get();

        return view('dashboard', [
            'dailySteps' => $dailySteps,
            'sleepMinutes' => $sleepMinutes,
            'sleepHours' => round($sleepMinutes / 60, 1),
            'latestHeartRate' => $latestHeartRate,
            'latestHeartRateValue' => $this->heartRateValue($latestHeartRate),
            'habits' => $habits,
            'recentMetrics' => $recentMetrics,
            'selectedDay' => $selectedDay,
        ]);
    }

    private function heartRateValue(?HealthMetric $metric): ?float
    {
        if (! $metric) {
            return null;
        }

        return data_get($metric->value, 'bpm_avg')
            ?? data_get($metric->value, 'samples.0.bpm');
    }
}

