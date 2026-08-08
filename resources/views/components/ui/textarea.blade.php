@props([
    'label' => null,
    'name' => null,
    'placeholder' => null,
    'rows' => 3,
])

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="text-sm font-medium text-ink">{{ $label }}</label>
    @endif
    <textarea
        @if($name) name="{{ $name }}" id="{{ $name }}" @endif
        rows="{{ $rows }}"
        @if($placeholder) placeholder="{{ $placeholder }}" @endif
        {{ $attributes->merge(['class' => 'w-full rounded-md border border-line bg-white px-3 py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500']) }}
    >{{ old($name, $slot) }}</textarea>
</div>
