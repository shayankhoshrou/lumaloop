@props([
    'habit',
    'selectedDay',
])

@php
    $completed = $habit->logs->isNotEmpty();
@endphp

<article class="flex flex-col gap-4 border-b border-white/10 py-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-3">
            <span @class([
                'grid h-9 w-9 place-items-center rounded-lg border text-sm font-black',
                'border-lime-300 bg-lime-300 text-stone-950' => $completed,
                'border-white/15 bg-white/5 text-white/50' => ! $completed,
            ])>
                {{ $completed ? '✓' : '·' }}
            </span>
            <div>
                <h3 class="font-bold text-white">{{ $habit->name }}</h3>
                @if ($habit->description)
                    <p class="text-sm text-white/55">{{ $habit->description }}</p>
                @endif
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('habits.logs.store', $habit) }}" class="flex items-center gap-2">
        @csrf
        <input type="hidden" name="log_date" value="{{ $selectedDay->toDateString() }}">
        <input
            name="notes"
            type="text"
            placeholder="Optional note"
            class="h-10 w-full rounded-md border-white/10 bg-white/5 text-sm text-white placeholder:text-white/35 focus:border-lime-300 focus:ring-lime-300 sm:w-48"
        >
        <button
            type="submit"
            class="h-10 rounded-md bg-white px-4 text-sm font-black text-stone-950 transition hover:bg-lime-300"
        >
            {{ $completed ? 'Update' : 'Log' }}
        </button>
    </form>
</article>

