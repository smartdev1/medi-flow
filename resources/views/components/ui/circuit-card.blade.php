@props([
    'name',
    'id',
    'meta' => null,
    'tone' => 'neutral',
    'badge' => null,
    'patientId' => null,
])

@php
    $accents = [
        'neutral' => 'border-l-line',
        'brand' => 'border-l-brand-500',
        'success' => 'border-l-success',
        'warning' => 'border-l-warning',
        'danger' => 'border-l-danger',
    ];
@endphp

@if($patientId)
    <a {{ $attributes->merge(['href' => route('patients.show', $patientId), 'class' => 'block rounded-md border border-line border-l-4 bg-white p-3 shadow-card hover:bg-surface-sunken ' . ($accents[$tone] ?? $accents['neutral'])]) }}>
@else
    <div {{ $attributes->merge(['class' => 'rounded-md border border-line border-l-4 bg-white p-3 shadow-card ' . ($accents[$tone] ?? $accents['neutral'])]) }}>
@endif
    <div class="flex items-start justify-between gap-2">
        <p class="text-sm font-medium leading-tight text-ink">{{ $name }}</p>
        @if ($badge)
            <x-ui.badge :tone="$badge['tone'] ?? 'neutral'" :dot="false" class="shrink-0">{{ $badge['label'] }}</x-ui.badge>
        @endif
    </div>
    <p class="mt-1 font-mono text-[11px] text-ink-muted">{{ $id }}</p>
    @if ($meta)
        <p class="mt-1.5 text-xs text-ink-soft">{{ $meta }}</p>
    @endif
@if($patientId)
    </a>
@else
    </div>
@endif
