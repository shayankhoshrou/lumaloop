@props([
    'label',
    'value',
    'caption' => null,
    'accent' => 'emerald',
])

@php
    $tones = [
        'emerald' => 'border-emerald-400/30 bg-emerald-400/10 text-emerald-200',
        'lime' => 'border-lime-300/30 bg-lime-300/10 text-lime-100',
        'sky' => 'border-sky-300/30 bg-sky-300/10 text-sky-100',
        'rose' => 'border-rose-300/30 bg-rose-300/10 text-rose-100',
    ];
@endphp

<section {{ $attributes->merge(['class' => 'rounded-lg border p-5 shadow-sm '.$tones[$accent]]) }}>
    <p class="text-xs font-bold uppercase tracking-wide text-white/55">{{ $label }}</p>
    <div class="mt-4 text-4xl font-black tracking-tight text-white">{{ $value }}</div>

    @if ($caption)
        <p class="mt-3 text-sm leading-6 text-white/65">{{ $caption }}</p>
    @endif
</section>

