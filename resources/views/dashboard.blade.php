<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-wide text-lime-500">Samsung Health + manual habits</p>
                <h2 class="mt-2 text-2xl font-black tracking-tight text-gray-950">
                    Habit Intelligence Dashboard
                </h2>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                <label for="date" class="text-sm font-bold text-gray-600">Day</label>
                <input
                    id="date"
                    name="date"
                    type="date"
                    value="{{ $selectedDay->toDateString() }}"
                    class="h-10 rounded-md border-gray-300 text-sm shadow-sm focus:border-lime-500 focus:ring-lime-500"
                >
                <button class="h-10 rounded-md bg-gray-950 px-4 text-sm font-black text-white">
                    View
                </button>
            </form>
        </div>
    </x-slot>

    <div class="min-h-screen bg-stone-950 py-8 text-white">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-lime-300/30 bg-lime-300/10 px-4 py-3 text-sm font-bold text-lime-100">
                    {{ session('status') }}
                </div>
            @endif

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-metric-card
                    label="Daily steps"
                    :value="number_format($dailySteps)"
                    caption="Synced from Samsung Health through Android Health Connect."
                    accent="lime"
                />

                <x-metric-card
                    label="Sleep duration"
                    :value="intdiv($sleepMinutes, 60).'h '.($sleepMinutes % 60).'m'"
                    caption="Calculated from sleep sessions overlapping the selected day."
                    accent="sky"
                />

                <x-metric-card
                    label="Latest heart rate"
                    :value="$latestHeartRateValue ? round($latestHeartRateValue).' bpm' : 'No data'"
                    :caption="$latestHeartRate ? 'Last sample '.$latestHeartRate->start_time->diffForHumans() : 'Sync heart rate records from the mobile app.'"
                    accent="rose"
                />

                <x-metric-card
                    label="Habits completed"
                    :value="$habits->filter(fn ($habit) => $habit->logs->isNotEmpty())->count().' / '.$habits->count()"
                    caption="Manual habits logged for the selected date."
                    accent="emerald"
                />
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_420px]">
                <div class="rounded-lg border border-white/10 bg-white/[0.04] p-5 shadow-2xl shadow-black/20">
                    <div class="flex flex-col gap-4 border-b border-white/10 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-wide text-lime-300">Manual habit layer</p>
                            <h3 class="mt-1 text-xl font-black">Today’s habits</h3>
                        </div>

                        <form method="POST" action="{{ route('habits.store') }}" class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]">
                            @csrf
                            <input
                                name="name"
                                type="text"
                                placeholder="Habit name"
                                class="h-10 rounded-md border-white/10 bg-white/5 text-sm text-white placeholder:text-white/35 focus:border-lime-300 focus:ring-lime-300"
                                required
                            >
                            <input
                                name="description"
                                type="text"
                                placeholder="Description"
                                class="h-10 rounded-md border-white/10 bg-white/5 text-sm text-white placeholder:text-white/35 focus:border-lime-300 focus:ring-lime-300"
                            >
                            <button class="h-10 rounded-md bg-lime-300 px-4 text-sm font-black text-stone-950">
                                Add
                            </button>
                        </form>
                    </div>

                    <div class="divide-y divide-white/0">
                        @forelse ($habits as $habit)
                            <x-habit-row :habit="$habit" :selected-day="$selectedDay" />
                        @empty
                            <div class="py-12 text-center">
                                <p class="text-lg font-black">No manual habits yet.</p>
                                <p class="mt-2 text-sm text-white/55">Create the first habit above, then log it daily.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <aside class="rounded-lg border border-white/10 bg-white/[0.04] p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-lime-300">Recent sync feed</p>
                    <h3 class="mt-1 text-xl font-black">Health Connect records</h3>

                    <div class="mt-5 space-y-3">
                        @forelse ($recentMetrics as $metric)
                            <article class="rounded-lg border border-white/10 bg-stone-900/80 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-black">{{ str($metric->metric_type)->replace('_', ' ')->title() }}</p>
                                        <p class="mt-1 text-xs text-white/50">{{ $metric->source_app }}</p>
                                    </div>
                                    <time class="text-xs font-bold text-white/45">
                                        {{ $metric->start_time->format('M j, H:i') }}
                                    </time>
                                </div>

                                <pre class="mt-3 max-h-28 overflow-auto rounded-md bg-black/30 p-3 text-xs leading-5 text-white/65">{{ json_encode($metric->value, JSON_PRETTY_PRINT) }}</pre>
                            </article>
                        @empty
                            <p class="rounded-lg border border-dashed border-white/15 p-5 text-sm text-white/55">
                                No Health Connect data has been synced yet. Once the Android app calls
                                <code class="rounded bg-black/40 px-1 py-0.5">POST /api/health-sync</code>,
                                records will appear here.
                            </p>
                        @endforelse
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-app-layout>

