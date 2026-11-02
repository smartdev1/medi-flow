@props([
    'label' => 'Examens demandés',
    'placeholder' => 'Rechercher un examen (gly, NFS, sérologie...)',
])

{{--
    Prescription de plusieurs examens en une seule demande.

    Chaque examen est choisi dans le catalogue `lab_exams` ; le prix est résolu
    côté serveur à partir de ce même catalogue, il n'est ni affiché ni saisi
    ici. Un examen hors catalogue n'est donc pas saisissable : sans tarif de
    référence, il serait consommé au laboratoire sans jamais être facturé.

    La liste des examens retenus se déroule dans le flux, sous le champ : elle
    ne peut donc pas être rognée par le corps défilant du tiroir.
--}}
<div
    x-data="labExamPicker(@js([
        'searchUrl' => route('exams.search'),
        'items' => array_map(
            fn (array $item) => [
                'lab_exam_id' => $item['lab_exam_id'],
                'name' => $item['name'],
                'category' => $item['category'] ?? null,
            ],
            old('items', [])
        ),
    ]))"
    class="relative flex flex-col gap-3"
>
    <div class="flex flex-col gap-1.5">
        <label for="exam-search" class="text-sm font-medium text-ink">{{ $label }}</label>

        <div class="relative">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
                <x-ui.icon name="search" class="h-4 w-4" />
            </span>
            <span x-show="loading" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-ink-muted">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 animate-spin">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 9 9" />
                </svg>
            </span>
            <input
                type="text"
                id="exam-search"
                x-model="query"
                @input="onInput()"
                @focus="open = true"
                placeholder="{{ $placeholder }}"
                autocomplete="off"
                class="w-full rounded-md border border-line bg-white py-2.5 pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
        </div>

        {{-- Suggestions du catalogue, dans le flux du tiroir. --}}
        <div
            x-show="hasResults"
            x-cloak
            class="max-h-56 overflow-y-auto rounded-md border border-line bg-white py-1 shadow-card"
        >
            <template x-for="exam in results" :key="exam.id">
                <button
                    type="button"
                    @click="add(exam)"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-surface-sunken"
                >
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-sm font-medium text-ink" x-text="exam.name"></span>
                        <span class="truncate text-xs text-ink-muted" x-text="exam.category"></span>
                    </span>
                    <span
                        x-show="alreadyAdded(exam.id)"
                        x-cloak
                        class="shrink-0 text-xs text-ink-muted"
                    >déjà demandé</span>
                </button>
            </template>
        </div>

        <p x-show="hasNoMatch" x-cloak class="text-xs text-ink-muted">
            Aucun examen du catalogue ne correspond à « <span x-text="query.trim()"></span> ».
            Cet examen doit être ajouté au catalogue avant de pouvoir être demandé.
        </p>
    </div>

    {{-- Examens retenus. Le prix est porté par le catalogue : il n'apparaît
         pas ici, et l'identifiant transmis suffit au serveur pour le retrouver. --}}
    <div x-show="items.length > 0" x-cloak class="flex flex-col gap-2">
        <p class="text-xs font-medium text-ink-soft">
            <span x-text="items.length"></span> examen(s) à demander
        </p>

        <template x-for="(item, index) in items" :key="item.lab_exam_id">
            <div class="flex items-start gap-2 rounded-md border border-line bg-white px-3 py-2">
                <input type="hidden" :name="'items[' + index + '][lab_exam_id]'" :value="item.lab_exam_id">
                <input type="hidden" :name="'items[' + index + '][name]'" :value="item.name">

                <span class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-sm font-medium text-ink" x-text="item.name"></span>
                    <span x-show="item.category" class="truncate text-xs text-ink-muted" x-text="item.category"></span>
                </span>

                <button
                    type="button"
                    @click="remove(index)"
                    class="shrink-0 rounded-md p-1 text-ink-muted hover:bg-surface-muted hover:text-danger"
                    title="Retirer cet examen"
                >
                    <x-ui.icon name="x-circle" class="h-4 w-4" />
                </button>
            </div>
        </template>
    </div>

    <template x-if="items.length === 0">
        <p class="text-xs text-ink-muted">
            Aucun examen sélectionné : la demande ne peut pas être envoyée.
        </p>
    </template>
</div>