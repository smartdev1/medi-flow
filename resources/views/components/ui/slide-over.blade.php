@props(['title'])

<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" {{ $attributes->merge(['class' => 'inline-flex']) }}>
    <span @click="open = true" class="inline-flex cursor-pointer">{{ $trigger }}</span>

    <div x-show="open" x-cloak class="fixed inset-0 z-50">
        <div
            class="absolute inset-0 bg-ink/40"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="open = false"
        ></div>

        <div
            class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-xl"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="text-base font-semibold text-ink">{{ $title }}</h2>
                <button type="button" @click="open = false" class="rounded-md p-1.5 text-ink-muted hover:bg-surface-muted">
                    <x-ui.icon name="x-circle" class="h-5 w-5" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto px-5 py-5">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
