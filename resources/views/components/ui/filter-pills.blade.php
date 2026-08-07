@props([
    'items' => [],
    'label' => 'Filtrer',
    'icon' => 'filter',
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 rounded-lg border border-line bg-white p-4 sm:flex-row sm:items-center']) }}
    x-data="{
        activeFilter: '{{ collect($items)->firstWhere('active', true)['label'] ?? '' }}',
        filterData(label) {
            this.activeFilter = label;
            
            // Get all filter buttons
            const buttons = this.$el.querySelectorAll('button');
            buttons.forEach(button => {
                if (button.textContent.trim() === label) {
                    button.classList.add('border-brand-500', 'bg-brand-50', 'text-brand-700');
                    button.classList.remove('border-line', 'text-ink-soft', 'hover:border-brand-300', 'hover:text-brand-700');
                } else {
                    button.classList.remove('border-brand-500', 'bg-brand-50', 'text-brand-700');
                    button.classList.add('border-line', 'text-ink-soft', 'hover:border-brand-300', 'hover:text-brand-700');
                }
            });
            
            // Dispatch event for parent components to handle
            this.$dispatch('filter-changed', { filter: label });
        }
    }">
    <div class="flex items-center gap-2 text-sm font-medium text-ink-soft">
        <x-ui.icon :name="$icon" class="h-4 w-4" />
        {{ $label }}&nbsp;:
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach ($items as $item)
            <button type="button"
                    @click="filterData('{{ $item['label'] }}')"
                    class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors
                           {{ ($item['active'] ?? false) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-line text-ink-soft hover:border-brand-300 hover:text-brand-700' }}">
                {{ $item['label'] }}
            </button>
        @endforeach
    </div>
    @isset($trailing)
        <div class="flex items-center gap-2 text-sm text-ink-muted sm:ml-auto">
            {{ $trailing }}
        </div>
    @endisset
</div>
