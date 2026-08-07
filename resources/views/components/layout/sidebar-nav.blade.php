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

<nav class="flex-1 space-y-0.5 px-3">
    @foreach ($nav as $key => $item)
        <a href="{{ $item['href'] }}"
           class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-colors
                  {{ $active === $key
                        ? 'border-l-2 border-brand-500 bg-brand-50 font-semibold text-brand-700'
                        : 'border-l-2 border-transparent text-ink-soft hover:bg-surface-muted' }}">
            <x-ui.icon :name="$item['icon']" class="h-5 w-5" />
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>

<div class="border-t border-line px-3 py-4">
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm text-ink-soft hover:bg-surface-muted">
        <x-ui.icon name="cog" class="h-5 w-5" />
        <span>Paramètres</span>
    </a>
</div>
