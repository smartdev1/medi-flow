@props(['nav', 'active'])

<div class="flex items-center gap-3 px-6 py-6">
    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-brand-500 text-white">
        <x-ui.icon name="heart" class="h-5 w-5" />
    </span>
    <div class="leading-tight">
        <p class="font-semibold text-ink">Clinique Saint Matthieu</p>
        <p class="text-[11px] uppercase tracking-wide text-ink-muted">Espace professionnel</p>
    </div>
</div>

<nav class="flex-1 space-y-1 px-3">
    @foreach ($nav as $key => $item)
        @php
            $isActive = $active === $key || (isset($item['active_routes']) && request()->routeIs($item['active_routes']));
            $hasActiveChild = !empty($item['children']) && collect($item['children'])->contains(fn ($child) => request()->routeIs($child['route']));
        @endphp

        <div x-data="{ open: {{ $hasActiveChild ? 'true' : 'false' }} }">
            @if (!empty($item['children']))
                <button type="button"
                        @click="open = !open"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-left text-sm transition-colors
                               {{ $isActive
                                     ? 'border-l-2 border-brand-500 bg-brand-50 font-semibold text-brand-700'
                                     : 'border-l-2 border-transparent text-ink-soft hover:bg-surface-muted' }}">
                    <x-ui.icon :name="$item['icon']" class="h-5 w-5" />
                    <span>{{ $item['label'] }}</span>
                    <x-ui.icon name="chevron-down" class="ml-auto h-4 w-4 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                </button>
            @else
                <a href="{{ $item['href'] }}"
                   class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors
                          {{ $isActive
                                ? 'border-l-2 border-brand-500 bg-brand-50 font-semibold text-brand-700'
                                : 'border-l-2 border-transparent text-ink-soft hover:bg-surface-muted' }}">
                    <x-ui.icon :name="$item['icon']" class="h-5 w-5" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif

            @if (!empty($item['children']))
                <div x-show="open" class="ml-5 mt-1 space-y-1 border-l border-line pl-3">
                    @foreach ($item['children'] as $child)
                        @php
                            $childActive = request()->routeIs($child['route']);
                        @endphp
                        <a href="{{ $child['href'] }}"
                           class="flex items-center gap-2 rounded-md px-2 py-2 text-sm transition-colors
                                  {{ $childActive
                                        ? 'font-semibold text-brand-700'
                                        : 'text-ink-muted hover:bg-surface-muted' }}">
                            <x-ui.icon :name="$child['icon']" class="h-4 w-4" />
                            <span>{{ $child['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</nav>

<div class="border-t border-line px-3 py-4">
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm text-ink-soft hover:bg-surface-muted">
        <x-ui.icon name="cog" class="h-5 w-5" />
        <span>Paramètres</span>
    </a>
</div>
