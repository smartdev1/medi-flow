@props([
    'label',
    'value',
    'icon' => null,
    'trend' => null,
    'trendTone' => 'neutral',
])

@php
    $trendClasses = [
        'success' => 'text-success',
        'danger' => 'text-danger',
        'neutral' => 'text-ink-muted',
    ];
@endphp

<x-ui.card class="flex flex-col gap-3">
    <div class="flex items-start justify-between">
        <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">{{ $label }}</p>
        @if ($icon)
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-surface-muted text-ink-soft">
                <x-ui.icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
    </div>
    <div class="flex items-baseline gap-2">
        <span class="text-3xl font-semibold text-ink">{{ $value }}</span>
        @if ($trend)
            <span class="text-xs font-medium {{ $trendClasses[$trendTone] ?? $trendClasses['neutral'] }}">{{ $trend }}</span>
        @endif
    </div>
</x-ui.card>
