@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => null,
    'value' => null,
])

@php
    $current = old($name ?? '', $value);
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="text-sm font-medium text-ink">{{ $label }}</label>
    @endif
    <select
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500']) }}
    >
        @if ($placeholder)
            <option value="" disabled @selected(!$current)>{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
</div>
