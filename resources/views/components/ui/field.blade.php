@props([
    'label' => null,
    'icon' => null,
    'type' => 'text',
    'name' => null,
    'placeholder' => null,
])

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label @if($name) for="{{ $name }}" @endif class="text-sm font-medium text-ink">{{ $label }}</label>
    @endif
    <div class="relative">
        @if ($icon)
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
                <x-ui.icon :name="$icon" class="h-4 w-4" />
            </span>
        @endif
        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" id="{{ $name }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $attributes->merge(['class' => 'w-full rounded-md border border-line bg-white py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 ' . ($icon ? 'pl-10 pr-3' : 'px-3')]) }}
        />
    </div>
</div>
