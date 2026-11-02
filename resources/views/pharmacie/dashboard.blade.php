<x-layout.app-shell title="Pharmacie" active="pharmacie" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Pharmacie</h1>
            <p class="mt-1 text-sm text-ink-muted">Délivrance des ordonnances et encaissement des ventes.</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="En attente de délivrance" value="{{ $awaitingCount }}" icon="capsule" trend="Ordonnances reçues" trend-tone="neutral" />
        <x-ui.stat-card label="Alertes de stock" value="{{ $shortageCount }}" icon="alert-triangle" trend="Réapprovisionnement requis" trend-tone="danger" />
        <x-ui.stat-card label="Délivrées aujourd'hui" value="{{ $dispensedCount }}" icon="check-circle" trend="Dossiers clôturés" trend-tone="success" />
    </div>

    {{--
        Vente directe : un patient peut se présenter sans ordonnance — des gants
        demandés par le médecin, par exemple.

        La recherche du patient est dans le flux de la page, et non derrière un
        tiroir : le pharmacien doit pouvoir composer la vente en gardant la
        file des ordonnances visible, et non masquer la moitié de son écran pour
        saisir un nom.
    --}}
    <x-ui.card class="mt-6">
        <form method="POST" action="{{ route('pharmacie.sell') }}" class="flex flex-col gap-4">
            @csrf

            <div class="flex flex-col gap-1 border-b border-line pb-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-ink">Vente au comptoir</h2>
                    <p class="mt-0.5 text-sm text-ink-muted">
                        Médicaments vendus sans ordonnance. Le prix est celui du catalogue.
                    </p>
                </div>
            </div>

            <x-ui.pharmacy-sale :method-options="$methodOptions" />

            <div class="flex justify-end gap-2 border-t border-line pt-4">
                <x-ui.button type="submit" class="w-full justify-center sm:w-auto">Encaisser la vente</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.queue-list title="Ordonnances à traiter" :items="$queue" class="lg:col-span-1" />

        <x-ui.card :padded="false" class="lg:col-span-2 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                            <th class="px-5 py-3 font-semibold">Patient</th>
                            <th class="px-5 py-3 font-semibold">Médicaments</th>
                            <th class="px-5 py-3 font-semibold">Stock</th>
                            <th class="px-5 py-3 font-semibold">Statut</th>
                            <th class="px-5 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($orders as $order)
                            <tr class="hover:bg-surface-sunken">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :initials="$order->patient->initials" size="sm" />
                                        <div>
                                            <p class="font-medium text-ink">{{ $order->patient->full_name }}</p>
                                            <p class="font-mono text-xs text-ink-muted">{{ $order->patient->code }}</p>
                                            <p class="text-xs text-ink-muted">Prescrit par {{ $order->prescribedBy?->name ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-ink-soft">{{ $order->medications_summary }}</td>
                                <td class="px-5 py-3.5">
                                    <x-ui.badge :tone="$order->hasSufficientStock() ? 'success' : 'danger'">
                                        {{ $order->hasSufficientStock() ? 'Disponible' : 'Insuffisant' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-ui.badge :tone="$order->status_tone">{{ $order->status }}</x-ui.badge>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if ($order->next_action)
                                        <x-ui.slide-over title="Délivrer l'ordonnance">
                                            <x-slot:trigger>
                                                <x-ui.button size="sm" type="button">{{ $order->next_action }}</x-ui.button>
                                            </x-slot:trigger>

                                            <form method="POST" action="{{ route('prescriptions.dispense', $order) }}" class="flex flex-col gap-4">
                                                @csrf

                                                <p class="text-sm text-ink-muted">Patient&nbsp;: <span class="font-medium text-ink">{{ $order->patient->full_name }}</span> ({{ $order->patient->code }})</p>

                                                <div class="rounded-md border border-line bg-surface-sunken p-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Médicaments</p>
                                                    <ul class="mt-2 flex flex-col gap-1 text-sm text-ink-soft">
                                                        @foreach ($order->items as $item)
                                                            <li class="flex justify-between">
                                                                <span>{{ $item->name }} × {{ $item->quantity }}</span>
                                                                <span class="font-mono">{{ number_format($item->line_total, 0, ',', ' ') }} FCFA</span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    <div class="mt-2 flex justify-between border-t border-line pt-2 text-sm font-semibold text-ink">
                                                        <span>Total à encaisser</span>
                                                        <span class="font-mono">{{ $order->formatted_total_amount }}</span>
                                                    </div>
                                                </div>

                                                @unless ($order->hasSufficientStock())
                                                    <p class="flex items-center gap-1.5 text-xs font-medium text-danger">
                                                        <x-ui.icon name="alert-triangle" class="h-3.5 w-3.5" />
                                                        Stock insuffisant pour un ou plusieurs médicaments — la délivrance sera refusée.
                                                    </p>
                                                @endunless

                                                <div>
                                                    <x-ui.select label="Moyen de paiement" name="method" placeholder="Sélectionner" :options="$methodOptions" required />
                                                    <x-input-error :messages="$errors->get('method')" class="mt-1" />
                                                </div>

                                                <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                                    <x-ui.button type="submit" class="w-full justify-center">Délivrer &amp; encaisser</x-ui.button>
                                                </div>
                                            </form>
                                        </x-ui.slide-over>
                                    @elseif ($order->status === \App\Models\Prescription::STATUS_DISPENSED)
                                        <p class="text-xs text-ink-muted">Par {{ $order->dispensedBy?->name ?? '—' }}</p>
                                    @else
                                        <span class="text-xs text-ink-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

</x-layout.app-shell>
