@props(['align' => 'right'])

<div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
    <div @click="open = !open" class="cursor-pointer">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click="open = false"
        class="absolute {{ $align === 'right' ? 'right-0' : 'left-0' }} top-full z-30 mt-2 w-60 overflow-hidden rounded-md border border-line bg-white shadow-card-hover"
    >
        {{ $slot }}
    </div>
</div>
