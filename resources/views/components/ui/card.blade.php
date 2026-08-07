@props([
    'padded' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white border border-line rounded-lg shadow-card ' . ($padded ? 'p-6' : '')]) }}>
    {{ $slot }}
</div>
