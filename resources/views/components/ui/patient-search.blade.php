@props([
    'name' => 'patient_id',
    'label' => 'Patient',
    'options' => [],
    'placeholder' => 'Rechercher par CSM-XXXX ou par nom...',
])

@php
    $normalized = collect($options)->map(function ($option) {
        if ($option instanceof \App\Models\Patient) {
            return [
                'id' => $option->id,
                'code' => $option->code,
                'name' => $option->full_name,
                'first_name' => $option->first_name,
                'last_name' => $option->last_name,
                'sex' => $option->sex,
                'birth_date' => optional($option->birth_date)->format('Y-m-d'),
                'phone' => $option->phone,
                'coverage' => $option->coverage,
            ];
        }

        return $option;
    })->values();

    $selectedId = old($name);
    $selected = $normalized->firstWhere('id', (int) $selectedId);
@endphp

<div
    x-data="{
        query: @js($selected ? $selected['code'].' — '.$selected['name'] : ''),
        open: false,
        selectedId: @js($selectedId ? (int) $selectedId : null),
        options: @js($normalized),
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (! q) return this.options;
            return this.options.filter(o => o.code.toLowerCase().includes(q) || o.name.toLowerCase().includes(q));
        },
        select(option) {
            this.selectedId = option.id;
            this.query = option.code + ' — ' + option.name;
            this.open = false;
            this.$dispatch('patient-selected', option);
        },
    }"
    class="relative flex flex-col gap-1.5"
    @click.outside="open = false"
>
    @if ($label)
        <label class="text-sm font-medium text-ink">{{ $label }}</label>
    @endif

    <input type="hidden" name="{{ $name }}" :value="selectedId">

    <div class="relative">
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
            <x-ui.icon name="search" class="h-4 w-4" />
        </span>
        <input
            type="text"
            x-model="query"
            @focus="open = true"
            @input="open = true; if (selectedId !== null) { selectedId = null; $dispatch('patient-search-cleared'); }"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            {{ $attributes->merge(['class' => 'w-full rounded-md border border-line bg-white py-2.5 pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500']) }}
        >
    </div>

    <div
        x-show="open && filtered.length > 0"
        x-cloak
        class="absolute top-full z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-line bg-white py-1 shadow-card-hover"
    >
        <template x-for="option in filtered" :key="option.id">
            <button
                type="button"
                @click="select(option)"
                class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-surface-sunken"
            >
                <span class="font-medium text-ink" x-text="option.name"></span>
                <span class="font-mono text-xs text-ink-muted" x-text="option.code"></span>
            </button>
        </template>
    </div>

    <p
        x-show="open && query.trim() !== '' && filtered.length === 0"
        x-cloak
        class="absolute top-full z-20 mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink-muted shadow-card"
    >
        Aucun patient trouvé.
    </p>
</div>
