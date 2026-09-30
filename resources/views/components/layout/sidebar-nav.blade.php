@props(['nav', 'active', 'badgeCounts'])

@php
    $activeRoute = request()->route()?->getName();
    $currentUser = auth()->user();
@endphp

<div class="flex items-center gap-3 px-6 py-6">
    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-brand-500 text-white">
        <x-ui.icon name="heart" class="h-5 w-5" />
    </span>
    <div class="leading-tight">
        <p class="font-semibold text-ink">Clinique Saint Matthieu</p>
        <p class="text-[11px] uppercase tracking-wide text-ink-muted">Espace professionnel</p>
    </div>
</div>

<nav class="flex-1 space-y-0.5 px-3" aria-label="Navigation principale">
    @foreach ($nav as $section)
        @if ($section['type'] === 'section')

            {{-- Flat section with items (Parcours Patient, Administratif) --}}
            @if (!empty($section['items']))
                <div class="px-3 py-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-ink-muted">{{ $section['label'] }}</p>
                </div>
                <div class="mb-1 border-b border-line"></div>

                @foreach ($section['items'] as $itemKey => $item)
                    @php $itemActive = in_array($activeRoute, $item['active_routes'] ?? []); @endphp
                    <a href="{{ $item['href'] }}"
                       class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors
                              {{ $itemActive
                                    ? 'border-l-2 border-brand-500 bg-brand-50 font-semibold text-brand-700'
                                    : 'border-l-2 border-transparent text-ink-soft hover:bg-surface-muted' }}">
                        <x-ui.icon :name="$item['icon']" class="h-5 w-5" />
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if (!empty($badgeCounts[$itemKey]) && $badgeCounts[$itemKey] > 0)
                            <span class="flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-danger px-1.5 text-xs font-semibold text-white"
                                  x-text="badges['{{ $itemKey }}']">{{ $badgeCounts[$itemKey] }}</span>
                        @endif
                    </a>
                @endforeach

            {{-- Parent section with collapsible children (Hospitalisation, Gestion).
                 Les enfants sont déjà filtrés par rôle dans app-shell : une
                 section sans lien visible en a été retirée. --}}
            @elseif (!empty($section['children']))
                @php
                    $childRoutes = collect($section['children'])->pluck('route')->all();
                    $parentActive = in_array($activeRoute, $section['active_routes'] ?? [])
                        || in_array($activeRoute, $childRoutes);
                    $isOpen = $parentActive ? 'true' : 'false';
                @endphp
                <div x-data="{ open: {{ $isOpen }} }">
                    <button type="button"
                            @click="open = !open"
                            class="flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors
                                   {{ $parentActive
                                        ? 'border-l-2 border-brand-500 bg-brand-50 font-semibold text-brand-700'
                                        : 'border-l-2 border-transparent text-ink-soft hover:bg-surface-muted' }}">
                        <x-ui.icon :name="$section['icon']" class="h-5 w-5" />
                        <span class="flex-1 text-left">{{ $section['label'] }}</span>
                        @if (!empty($section['badge']) && $section['badge'] > 0)
                            <span class="flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-danger px-1.5 text-xs font-semibold text-white"
                                  x-text="badges['{{ $section['key'] }}']">{{ $section['badge'] }}</span>
                        @endif
                        <span :class="open ? 'rotate-180' : ''" class="inline-flex transition-transform duration-200">
                            <x-ui.icon name="chevron-down" class="h-4 w-4 text-ink-muted" />
                        </span>
                    </button>

                    <div x-show="open" x-transition class="space-y-0.5 pl-4">
                        @foreach ($section['children'] as $child)
                            @php $childActive = $activeRoute === $child['route']; @endphp
                            <a href="{{ $child['href'] }}"
                               class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors
                                      {{ $childActive
                                            ? 'border-l-2 border-brand-500 bg-brand-50 font-semibold text-brand-700'
                                            : 'border-l-2 border-transparent text-ink-soft hover:bg-surface-muted' }}">
                                <x-ui.icon :name="$child['icon']" class="h-5 w-5" />
                                <span class="flex-1">{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    @endforeach
</nav>
