@props([
    'methodOptions' => [],
])

{{--
    Point de vente pharmacie : vente de médicaments hors ordonnance.

    Un patient peut se présenter sans passer par une consultation — sur demande
    du médecin, pour des gants par exemple — donc aucune prescription n'est
    exigée. La vente n'est rattachée à aucune visite : seul le dossier patient
    est porté, et le prix est résolu côté serveur depuis `medications`.
--}}
<div
    x-data="pharmacySale(@js([
        'searchUrl' => route('pharmacie.medications.search'),
        'patientSearchUrl' => route('pharmacie.patients.search'),
        'methodOptions' => $methodOptions,
        'items' => array_map(
            fn (array $item) => [
                'medication_id' => $item['medication_id'],
                'name' => $item['name'],
                'unit' => $item['unit'] ?? null,
                'unit_price' => $item['unit_price'],
                'stock_quantity' => $item['stock_quantity'],
                'quantity' => $item['quantity'],
            ],
            old('items', [])
        ),
        'patient' => old('patient_id') ? [
            'id' => old('patient_id'),
            'first_name' => old('patient_name', ''),
            'last_name' => old('patient_last_name', ''),
            'code' => old('patient_code', ''),
        ] : null,
        'method' => old('method', ''),
    ]))"
    class="relative flex flex-col gap-5"
>
    {{-- Patient enregistré, sans visite ni ordonnance. --}}
    <div class="flex flex-col gap-1.5">
        <label for="sale-patient" class="text-sm font-medium text-ink">Patient</label>

        <input type="hidden" name="patient_id" :value="patient ? patient.id : ''">
        <input type="hidden" name="patient_name" :value="patient ? patient.first_name : ''">
        <input type="hidden" name="patient_last_name" :value="patient ? patient.last_name : ''">
        <input type="hidden" name="patient_code" :value="patient ? patient.code : ''">

        <div class="relative">
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
                <x-ui.icon name="user" class="h-4 w-4" />
            </span>
            <input
                type="text"
                id="sale-patient"
                x-model="patientQuery"
                @input="onPatientInput()"
                @focus="patientOpen = true"
                value="{{ old('patient_name') ? trim(old('patient_name').' '.old('patient_last_name', '')) : '' }}"
                placeholder="Rechercher le patient enregistré..."
                autocomplete="off"
                class="w-full rounded-md border border-line bg-white py-2.5 pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
        </div>

        <div x-show="patientOpen && patientResults.length > 0" x-cloak
             class="max-h-48 overflow-y-auto rounded-md border border-line bg-white py-1 shadow-card">
            <template x-for="candidate in patientResults" :key="candidate.id">
                <button type="button" @click="selectPatient(candidate)"
                        class="flex w-full flex-col px-3 py-2 text-left hover:bg-surface-sunken">
                    <span class="text-sm font-medium text-ink"
                          x-text="candidate.first_name + ' ' + candidate.last_name"></span>
                    <span class="font-mono text-xs text-ink-muted" x-text="candidate.code"></span>
                </button>
            </template>
        </div>

        <x-input-error :messages="$errors->get('patient_id')" class="mt-1" />
    </div>

    {{-- Médicaments vendus. --}}
    <div class="flex flex-col gap-1.5">
        <label for="sale-search" class="text-sm font-medium text-ink">Médicaments vendus</label>

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
                id="sale-search"
                x-model="query"
                @input="onInput()"
                @focus="open = true"
                placeholder="Rechercher un médicament..."
                autocomplete="off"
                class="w-full rounded-md border border-line bg-white py-2.5 pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
            >
        </div>

        <div x-show="hasResults" x-cloak
             class="max-h-56 overflow-y-auto rounded-md border border-line bg-white py-1 shadow-card">
            <template x-for="medication in results" :key="medication.id">
                <button type="button" @click="add(medication)"
                        :disabled="medication.stock_quantity < 1"
                        class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left hover:bg-surface-sunken disabled:cursor-not-allowed disabled:opacity-50">
                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-sm font-medium text-ink" x-text="medication.name"></span>
                        <span class="truncate text-xs text-ink-muted"
                              x-text="medication.stock_quantity < 1
                                  ? 'Rupture de stock'
                                  : 'Stock : ' + medication.stock_quantity"></span>
                    </span>
                    <span class="shrink-0 font-mono text-xs text-ink-soft"
                          x-text="Number(medication.unit_price).toLocaleString('fr-FR') + ' FCFA'"></span>
                </button>
            </template>
        </div>

        <x-input-error :messages="$errors->get('items')" class="mt-1" />
        <x-input-error :messages="$errors->get('items.*.medication_id')" class="mt-1" />
        <x-input-error :messages="$errors->get('items.*.quantity')" class="mt-1" />
    </div>

    {{-- Panier. Le prix est celui du catalogue, à titre indicatif : c'est le
         serveur qui refait la multiplication au moment d'encaisser. --}}
    <div x-show="items.length > 0" x-cloak class="flex flex-col gap-2">
        <template x-for="(item, index) in items" :key="item.medication_id">
            <div class="flex items-start gap-3 rounded-md border border-line bg-white px-3 py-2">
                <input type="hidden" :name="'items[' + index + '][medication_id]'" :value="item.medication_id">
                <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">

                <span class="flex min-w-0 flex-1 flex-col">
                    <span class="truncate text-sm font-medium text-ink" x-text="item.name"></span>
                    <span class="truncate text-xs text-ink-muted"
                          x-text="Number(item.unit_price).toLocaleString('fr-FR') + ' FCFA l\'unité'"></span>
                    <span x-show="item.quantity === item.stock_quantity && item.stock_quantity > 0"
                          class="truncate text-xs font-medium text-danger"
                          x-text="'Quantité limitée au stock : ' + item.stock_quantity"></span>
                </span>

                <input
                    type="number"
                    min="1"
                    :max="item.stock_quantity"
                    :value="item.quantity"
                    :data-medication="item.medication_id"
                    @input="setQuantity(item.medication_id, $event.target.value); syncQuantityField(item.medication_id)"
                    class="w-16 rounded-md border border-line px-2 py-1 text-sm text-ink focus:border-brand-500 focus:outline-none"
                    aria-label="Quantité vendue"
                >

                <button type="button" @click="remove(index)"
                        class="shrink-0 rounded-md p-1 text-ink-muted hover:bg-surface-muted hover:text-danger"
                        title="Retirer ce médicament">
                    <x-ui.icon name="x-circle" class="h-4 w-4" />
                </button>
            </div>
        </template>

        <div class="flex items-center justify-between border-t border-line pt-2">
            <span class="text-sm font-medium text-ink">Total à encaisser</span>
            <span class="font-mono text-base font-semibold text-ink"
                  x-text="Number(total).toLocaleString('fr-FR') + ' FCFA'"></span>
        </div>
    </div>

    <template x-if="items.length === 0">
        <p class="text-xs text-ink-muted">
            Aucun médicament au panier : la vente ne peut pas être encaissée.
        </p>
    </template>

    <div>
        <label for="sale-method" class="text-sm font-medium text-ink">Moyen de paiement</label>
        <select
            id="sale-method"
            name="method"
            x-model="method"
            required
            class="mt-1.5 w-full rounded-md border border-line bg-white px-3 py-2.5 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
        >
            <option value="">Sélectionner</option>
            @foreach ($methodOptions as $option)
                <option value="{{ $option }}">{{ $option }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('method')" class="mt-1" />
    </div>
</div>
