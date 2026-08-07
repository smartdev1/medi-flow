<x-layout.app-shell title="Logistique" active="logistique" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Logistique</h1>
            <p class="mt-1 text-sm text-ink-muted">Gestion du stock de médicaments et des commandes fournisseur.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <x-ui.slide-over title="Nouveau médicament">
                <x-slot:trigger>
                    <x-ui.button variant="secondary">
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        Nouveau médicament
                    </x-ui.button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('medications.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <x-ui.field label="Nom du médicament" name="name" placeholder="Ex : Amoxicilline 500mg" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.field label="Unité" name="unit" placeholder="Ex : Comprimé, Flacon, Sachet" :value="old('unit')" required />
                        <x-input-error :messages="$errors->get('unit')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.field label="Prix unitaire (FCFA)" name="unit_price" type="number" min="0" :value="old('unit_price')" required />
                        <x-input-error :messages="$errors->get('unit_price')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.field label="Stock initial" name="stock_quantity" type="number" min="0" :value="old('stock_quantity', 0)" required />
                        <x-input-error :messages="$errors->get('stock_quantity')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.field label="Seuil d'alerte" name="low_stock_threshold" type="number" min="0" :value="old('low_stock_threshold', 10)" required />
                        <p class="mt-1 text-xs text-ink-muted">Une alerte de stock bas s'affiche sous ce seuil.</p>
                        <x-input-error :messages="$errors->get('low_stock_threshold')" class="mt-1" />
                    </div>

                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                        <x-ui.button type="submit" class="w-full justify-center">Ajouter au catalogue</x-ui.button>
                    </div>
                </form>
            </x-ui.slide-over>

            <x-ui.slide-over title="Nouvelle commande">
                <x-slot:trigger>
                    <x-ui.button>
                        <x-ui.icon name="archive-box" class="h-4 w-4" />
                        Nouvelle commande
                    </x-ui.button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('medication-orders.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div x-data="{ items: @js(old('items', [['medication_id' => '', 'quantity' => 1]])) }" class="flex flex-col gap-2">
                        <label class="text-sm font-medium text-ink">Médicaments à commander</label>

                        <template x-for="(item, index) in items" :key="index">
                            <div class="flex items-end gap-2">
                                <div class="flex-1">
                                    <select
                                        :name="`items[${index}][medication_id]`"
                                        x-model="item.medication_id"
                                        class="w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                    >
                                        <option value="">Sélectionner un médicament</option>
                                        @foreach ($medications as $medication)
                                            <option value="{{ $medication->id }}">{{ $medication->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-24">
                                    <input
                                        type="number"
                                        min="1"
                                        :name="`items[${index}][quantity]`"
                                        x-model="item.quantity"
                                        placeholder="Qté"
                                        class="w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                    >
                                </div>
                                <button
                                    type="button"
                                    @click="items.length > 1 && items.splice(index, 1)"
                                    class="shrink-0 rounded-md p-2 text-ink-muted hover:bg-surface-muted"
                                >
                                    <x-ui.icon name="x-circle" class="h-4 w-4" />
                                </button>
                            </div>
                        </template>

                        <button
                            type="button"
                            @click="items.push({ medication_id: '', quantity: 1 })"
                            class="mt-1 flex items-center gap-1.5 self-start text-sm font-medium text-brand-600 hover:text-brand-700"
                        >
                            <x-ui.icon name="plus" class="h-4 w-4" />
                            Ajouter un médicament
                        </button>

                        <x-input-error :messages="$errors->get('items')" class="mt-1" />
                        <x-input-error :messages="$errors->get('items.*.medication_id')" class="mt-1" />
                        <x-input-error :messages="$errors->get('items.*.quantity')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.textarea label="Notes (facultatif)" name="notes" :rows="3" placeholder="Fournisseur, instructions particulières...">{{ old('notes') }}</x-ui.textarea>
                    </div>

                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                        <x-ui.button type="submit" class="w-full justify-center">Créer la commande</x-ui.button>
                    </div>
                </form>
            </x-ui.slide-over>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Médicaments au catalogue" value="{{ $medications->count() }}" icon="capsule" trend="Références suivies" trend-tone="neutral" />
        <x-ui.stat-card label="Alertes de stock bas" value="{{ $lowStockCount }}" icon="alert-triangle" trend="Sous le seuil d'alerte" trend-tone="danger" />
        <x-ui.stat-card label="Commandes en attente" value="{{ $pendingOrderCount }}" icon="archive-box" trend="À réceptionner" trend-tone="neutral" />
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">Catalogue &amp; stock</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Médicament</th>
                        <th class="px-5 py-3 font-semibold">Prix unitaire</th>
                        <th class="px-5 py-3 font-semibold">Stock</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Augmenter le stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($medications as $medication)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-ink">{{ $medication->name }}</p>
                                <p class="text-xs text-ink-muted">{{ $medication->unit }}</p>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-ink-soft">{{ $medication->formatted_unit_price }}</td>
                            <td class="px-5 py-3.5 font-mono text-ink-soft">{{ $medication->stock_quantity }}</td>
                            <td class="px-5 py-3.5">
                                @if ($medication->is_low_stock)
                                    <x-ui.badge tone="danger">Stock bas</x-ui.badge>
                                @else
                                    <x-ui.badge tone="success">Suffisant</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <form method="POST" action="{{ route('medications.adjust-stock', $medication) }}" class="flex items-center justify-end gap-2">
                                    @csrf
                                    <input
                                        type="number"
                                        name="quantity"
                                        min="1"
                                        placeholder="Qté"
                                        class="w-20 rounded-md border border-line bg-white py-1.5 px-2 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                        required
                                    >
                                    <x-ui.button size="sm" variant="secondary" type="submit">Ajouter</x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">Commandes de médicaments</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Référence</th>
                        <th class="px-5 py-3 font-semibold">Demandée par</th>
                        <th class="px-5 py-3 font-semibold">Contenu</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($orders as $order)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5 font-mono text-ink">{{ $order->reference }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">
                                {{ $order->requestedBy?->name ?? '—' }}
                                <p class="text-xs text-ink-muted">{{ $order->ordered_at->format('d/m/Y') }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $order->items_summary }}</td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$order->status_tone">{{ $order->status }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex justify-end gap-2">
                                    <x-ui.button size="sm" variant="secondary" :href="route('medication-orders.print', $order)" target="_blank">
                                        <x-ui.icon name="download" class="h-4 w-4" />
                                        Imprimer
                                    </x-ui.button>
                                    @if ($order->status === \App\Models\MedicationOrder::STATUS_PENDING)
                                        <form method="POST" action="{{ route('medication-orders.receive', $order) }}">
                                            @csrf
                                            <x-ui.button size="sm" type="submit">Marquer reçue</x-ui.button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

</x-layout.app-shell>
