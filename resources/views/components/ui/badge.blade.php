@props([
    'tone' => 'neutral',
    'dot' => true,
])

@php
    $tones = [
        'neutral' => 'bg-surface-muted text-ink-soft border-line',
        'brand' => 'bg-brand-50 text-brand-700 border-brand-200',
        'success' => 'bg-success-tint text-success border-success/20',
        'warning' => 'bg-warning-tint text-warning border-warning/20',
        'danger' => 'bg-danger-tint text-danger border-danger/20',
    ];

    $dots = [
        'neutral' => 'bg-ink-muted',
        'brand' => 'bg-brand-500',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-medium leading-none ' . ($tones[$tone] ?? $tones['neutral'])]) }}>
    @if ($dot)
        <span class="h-1.5 w-1.5 rounded-full {{ $dots[$tone] ?? $dots['neutral'] }}"></span>
    @endif
    {{ $slot }}
</span>
