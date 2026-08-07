@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-brand-500 text-white border border-brand-500 hover:bg-brand-600 hover:border-brand-600 focus-visible:ring-brand-200',
        'secondary' => 'bg-white text-ink border border-line hover:bg-surface-muted focus-visible:ring-brand-200',
        'ghost' => 'bg-transparent text-ink-soft border border-transparent hover:bg-surface-muted focus-visible:ring-brand-200',
        'danger' => 'bg-white text-danger border border-danger/30 hover:bg-danger-tint focus-visible:ring-danger/30',
    ];

    $sizes = [
        'sm' => 'text-xs px-3 py-1.5 gap-1.5',
        'md' => 'text-sm px-4 py-2 gap-2',
        'lg' => 'text-sm px-5 py-2.5 gap-2',
    ];

    $classes = 'inline-flex items-center justify-center rounded-md font-medium transition-colors duration-100 focus:outline-none focus-visible:ring-2 disabled:opacity-50 disabled:pointer-events-none '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
