@props([
    'initials',
    'tone' => 'brand',
    'size' => 'md',
])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'success' => 'bg-success-tint text-success',
        'warning' => 'bg-warning-tint text-warning',
        'danger' => 'bg-danger-tint text-danger',
        'neutral' => 'bg-surface-muted text-ink-soft',
    ];

    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full font-semibold ' . ($tones[$tone] ?? $tones['brand']) . ' ' . ($sizes[$size] ?? $sizes['md'])]) }}>
    {{ $initials }}
</span>
