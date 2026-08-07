<x-layout.app-shell title="Caisse" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Caisse</h1>
            <p class="mt-1 text-sm text-ink-muted">
                @if ($isToday)
                    Encaissement des consultations, analyses et ordonnances du jour.
                @else
                    Consultation des paiements du <span class="font-medium text-ink">{{ $currentDate->translatedFormat('l j F Y') }}</span>.
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($canCloseDay)
                <form method="POST" action="{{ route('caisse.cloturer') }}" onsubmit="return confirm('Clôturer votre caisse du {{ $currentDate->translatedFormat('d/m/Y') }} ? Cette action est irréversible.')">
                    @csrf
                    <input type="hidden" name="date" value="{{ $currentDate->toDateString() }}" />
                    <x-ui.button type="submit" variant="danger">
                        <x-ui.icon name="lock-closed" class="h-4 w-4" />
                        Clôturer ma caisse
                    </x-ui.button>
                </form>
            @endif

            <x-ui.slide-over title="Nouvel encaissement">
                <x-slot:trigger>
                    <x-ui.button>
                        <x-ui.icon name="credit-card" class="h-4 w-4" />
                        Nouvel encaissement
                    </x-ui.button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('payments.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="date" value="{{ $currentDate->toDateString() }}" />
                    <input type="hidden" name="cashier_id" value="{{ Auth::id() }}" />

                    <div>
                        <x-ui.patient-search :options="$patientOptions" />
                        <x-input-error :messages="$errors->get('patient_id')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.field label="Service facturé" name="service" placeholder="Ex : Consultation — Généraliste" :value="old('service')" required />
                        <x-input-error :messages="$errors->get('service')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.field label="Montant (FCFA)" name="amount" type="number" placeholder="10000" :value="old('amount')" required min="0" />
                        <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.select label="Mode de paiement" name="method" placeholder="Sélectionner" :options="$methodOptions" required />
                        <x-input-error :messages="$errors->get('method')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.select label="Statut" name="status" placeholder="Sélectionner" :options="$statusOptions" required />
                        <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    </div>

                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                        <x-ui.button type="submit" class="w-full justify-center">Enregistrer l'encaissement</x-ui.button>
                    </div>
                </form>
            </x-ui.slide-over>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <x-ui.stat-card label="En attente de paiement" value="{{ $pendingCount }}" icon="clock" trend="À encaisser" trend-tone="neutral" />
        <x-ui.stat-card label="Encaissé" value="{{ number_format($cashedInToday, 0, ',', ' ') }} FCFA" icon="credit-card" trend="{{ $transactions->count() }} transaction(s)" trend-tone="success" />
        <x-ui.stat-card label="Impayés partiels" value="{{ $partialCount }}" icon="alert-triangle" trend="Suivi requis" trend-tone="danger" />
        <x-ui.stat-card
            :label="$isToday ? 'Journée en cours' : 'Journée clôturée'"
            :value="$isToday ? $openCount . ' ouvert(s)' : 'Tous soldés'"
            icon="{{ $isToday ? 'lock-open' : 'lock-closed' }}"
            :trend="$isToday ? $closedCount . ' déjà clôturé(s)' : 'Lecture seule'"
            :trend-tone="$isToday ? 'neutral' : 'success'"
        />
    </div>

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('caisse.index') }}" class="flex flex-col gap-3 rounded-lg border border-line bg-white p-4 sm:flex-row sm:items-center">
            <div class="flex items-center gap-2 text-sm font-medium text-ink-soft">
                <x-ui.icon name="filter" class="h-4 w-4" />
                Filtrer par statut :
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($filters as $filter)
                    <button
                        type="submit"
                        name="status"
                        value="{{ $filter['label'] }}"
                        class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors {{ $filter['active'] ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-line text-ink-soft hover:border-brand-300 hover:text-brand-700' }}"
                    >
                        {{ $filter['label'] }}
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="date" value="{{ $currentDate->toDateString() }}" />
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('caisse.index') }}" class="flex items-center gap-2">
                <label for="date" class="text-xs font-medium uppercase tracking-wide text-ink-muted">Date</label>
                <input
                    type="date"
                    id="date"
                    name="date"
                    value="{{ $currentDate->toDateString() }}"
                    max="{{ now()->toDateString() }}"
                    class="rounded-md border border-line bg-white px-3 py-1.5 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                />
                <x-ui.button type="submit" size="sm" variant="secondary">Afficher</x-ui.button>
            </form>

            <a href="{{ route('caisse.historique') }}">
                <x-ui.button size="sm" variant="secondary">
                    <x-ui.icon name="archive" class="h-4 w-4" />
                    Historique
                </x-ui.button>
            </a>
        </div>
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Patient</th>
                        <th class="px-5 py-3 font-semibold">Caissier</th>
                        <th class="px-5 py-3 font-semibold">Service</th>
                        <th class="px-5 py-3 font-semibold">Montant</th>
                        <th class="px-5 py-3 font-semibold">Mode de paiement</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($transactions as $transaction)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :initials="$transaction['initials']" size="sm" />
                                    <div>
                                        <p class="font-medium text-ink">{{ $transaction['name'] }}</p>
                                        <p class="font-mono text-xs text-ink-muted">{{ $transaction['id'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $transaction['cashier'] ?? 'N/A' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $transaction['service'] }}</td>
                            <td class="px-5 py-3.5 font-mono font-medium text-ink">{{ $transaction['amount'] }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $transaction['method'] }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col items-start gap-1">
                                    <x-ui.badge :tone="$transaction['status_tone']">{{ $transaction['status'] }}</x-ui.badge>
                                    @if ($transaction['closed'])
                                        <x-ui.badge tone="success">
                                            <x-ui.icon name="lock-closed" class="h-3 w-3" />
                                            Clôturé {{ $transaction['closed_at'] }}
                                        </x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($transaction['status'] === 'Payé' && ! $transaction['closed'])
                                    <a href="{{ route('payments.receipt', $transaction['payment_id']) }}" target="_blank">
                                        <x-ui.button variant="secondary" size="sm">
                                            <x-ui.icon name="download" class="h-4 w-4" />
                                            Reçu
                                        </x-ui.button>
                                    </a>
                                @elseif ($transaction['closed'])
                                    <x-ui.button variant="secondary" size="sm" disabled>
                                        <x-ui.icon name="lock-closed" class="h-4 w-4" />
                                        Verrouillé
                                    </x-ui.button>
                                @elseif ($transaction['status'] === 'Partiellement payé' || $transaction['status'] === 'En attente')
                                    <x-ui.button 
                                        size="sm" 
                                        data-payment-id="{{ $transaction['id'] }}"
                                        data-amount="{{ $transaction['amount'] }}"
                                        data-name="{{ $transaction['name'] }}"
                                        onclick="handleEncaisser(this)"
                                    >
                                        Encaisser
                                    </x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-sm text-ink-muted">
                                Aucun paiement enregistré pour votre caisse aujourd'hui.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <script>
        function handleEncaisser(button) {
            const paymentId = button.getAttribute('data-payment-id');
            const amount = button.getAttribute('data-amount');
            const name = button.getAttribute('data-name');

            if (!paymentId) {
                alert('Identifiant de paiement manquant.');
                return;
            }

            if (!confirm('Confirmer l\'encaissement de ' + amount + ' FCFA pour ' + name + ' ?')) {
                return;
            }

            fetch('{{ route("payments.encaisser") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ payment_id: paymentId })
            })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const message = data.message || ('Erreur serveur: ' + response.status);
                    throw new Error(message);
                }
                return data;
            })
            .then((data) => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Réponse inattendue du serveur.');
                }
            })
            .catch((error) => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'encaissement : ' + error.message);
            });
        }
    </script>

</x-layout.app-shell>
