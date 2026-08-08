@props([
    'title' => "File d'attente",
    'items' => [],
])

<x-ui.card :padded="false" {{ $attributes }}>
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
        <h2 class="text-sm font-semibold text-ink">{{ $title }}</h2>
        <span class="text-xs text-ink-muted">{{ count($items) }} patients</span>
    </div>
    <ul class="divide-y divide-line">
        @forelse ($items as $item)
            <li>
                <a href="{{ $item['href'] ?? '#' }}" class="flex items-start gap-3 px-5 py-4 {{ ($item['selected'] ?? false) ? 'bg-brand-50' : 'hover:bg-surface-sunken' }}">
                    <x-ui.avatar :initials="$item['initials']" :tone="($item['urgent'] ?? false) ? 'danger' : 'brand'" size="sm" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-medium text-ink">{{ $item['name'] }}</p>
                            @if (!empty($item['time']))
                                <span class="font-mono text-xs text-ink-muted">{{ $item['time'] }}</span>
                            @endif
                        </div>
                        <p class="mt-0.5 truncate text-xs text-ink-muted">{{ $item['meta'] }}</p>
                        @if (($item['urgent'] ?? false) || !empty($item['badge']))
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if ($item['urgent'] ?? false)
                                    <x-ui.badge tone="danger">Urgent</x-ui.badge>
                                @endif
                                @if (!empty($item['badge']))
                                    <x-ui.badge :tone="$item['badge']['tone'] ?? 'neutral'">{{ $item['badge']['label'] }}</x-ui.badge>
                                @endif
                            </div>
                        @endif
                    </div>
                </a>
            </li>
        @empty
            <li class="px-5 py-8 text-center text-sm text-ink-muted">Aucun patient dans cette file.</li>
        @endforelse
    </ul>
</x-ui.card>
