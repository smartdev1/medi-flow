<x-layout.app-shell title="Caisse" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6" x-data="{
        counts: { pending: {{ $paniersEnCours }}, aPayer: {{ $aPayer }}, payes: {{ $payes }} },
        init() {
            setInterval(() => {
                const params = new URLSearchParams(window.location.search);
                fetch('{{ route('caisse.refresh') }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => {
                    if (data.paniersEnCours !== this.counts.pending || data.aPayer !== this.counts.aPayer || data.payes !== this.counts.payes) {
                        window.location.reload();
                    }
                })
                .catch(() => {});
            }, 15000);
        }
    }">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
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

            @if (isset($activeSession) && $activeSession)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-success/30 bg-success/10 px-3 py-1 text-sm font-medium text-success">
                        <span class="h-2 w-2 rounded-full bg-success"></span>
                        Session en cours depuis {{ $activeSession->opened_at->format('H:i') }} — Fonds d'ouverture : {{ number_format($activeSession->opening_balance, 0, ',', ' ') }} FCFA
                    </span>
                    <a href="{{ route('caisse.sessions.close') }}">
                        <x-ui.button size="sm" variant="danger">
                            <x-ui.icon name="lock-closed" class="h-4 w-4" />
                            Fermer ma session
                        </x-ui.button>
                    </a>
                </div>
            @else
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-warning/30 bg-warning/10 px-3 py-1.5 text-sm font-medium text-warning">
                        <span class="h-2 w-2 rounded-full bg-warning"></span>
                        Aucune session ouverte
                    </span>
                    <a href="{{ route('caisse.sessions.open') }}">
                        <x-ui.button size="sm" variant="primary">
                            <x-ui.icon name="lock-open" class="h-4 w-4" />
                            Ouvrir une session
                        </x-ui.button>
                    </a>
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (isset($activeSession) && $activeSession)
                <x-ui.slide-over title="Nouvel encaissement">
                    <x-slot:trigger>
                        <x-ui.button>
                            <x-ui.icon name="credit-card" class="h-4 w-4" />
                            Nouvel encaissement
                        </x-ui.button>
                    </x-slot:trigger>

                    <form method="POST" action="{{ route('payments.store') }}" class="flex flex-col gap-4" x-data="billingForm()" @patient-selected="onPatientSelected($event.detail)">
                        @csrf
                        <input type="hidden" name="date" value="{{ $currentDate->toDateString() }}" />
                        <input type="hidden" name="cashier_id" value="{{ Auth::id() }}" />

                        <div>
                            <x-ui.patient-search :options="$patientOptions" />
                            <x-input-error :messages="$errors->get('patient_id')" class="mt-1" />
                        </div>

                         <div>
                             <x-ui.field label="Service facture" name="service" id="service" placeholder="Ex : Consultation — Generaliste" :value="old('service')" required />
                             <x-input-error :messages="$errors->get('service')" class="mt-1" />
                         </div>

                         <div>
                             <x-ui.field label="Montant de base (FCFA)" name="base_amount" id="base_amount" type="number" placeholder="20000" :value="old('base_amount')" min="0" required />
                             <x-input-error :messages="$errors->get('base_amount')" class="mt-1" />
                         </div>

                         <div>
                             <x-ui.field label="Ticket moderateur (%)" name="taux" id="taux" type="number" placeholder="20" :value="old('taux', config('clinic.default_ticket_rate', 20))" min="0" max="100" step="0.01" required />
                             <x-input-error :messages="$errors->get('taux')" class="mt-1" />
                         </div>

                         <div>
                             <x-ui.field label="Montant restant (FCFA)" name="amount" id="amount" type="number" placeholder="Calcule automatiquement" :value="old('amount')" min="0" readonly />
                             <p class="mt-1 text-xs text-ink-muted">Calcule automatiquement : montant de base x (1 - ticket moderateur/100)</p>
                             <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                         </div>

                        <div>
                            <x-ui.select label="Mode de paiement" name="method" placeholder="Selectionner" :options="$methodOptions" required />
                            <x-input-error :messages="$errors->get('method')" class="mt-1" />
                        </div>

                        <div>
                            <x-ui.select label="Statut" name="status" placeholder="Selectionner" :options="$statusOptions" required />
                            <x-input-error :messages="$errors->get('status')" class="mt-1" />
                        </div>

                         <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                <x-ui.button type="submit" class="w-full justify-center">Enregistrer l'encaissement</x-ui.button>
                            </div>
                        </form>

                        <script>
                            function billingForm() {
                                return {
                                    patientActs: @js($patientActs),
                                    onPatientSelected(option) {
                                        const act = this.patientActs[option.id];
                                        const service = document.getElementById('service');
                                        const baseAmount = document.getElementById('base_amount');
                                        const amount = document.getElementById('amount');

                                        if (! act) {
                                            if (service) service.value = '';
                                            if (baseAmount) baseAmount.value = '';
                                            if (amount) amount.value = '';
                                            return;
                                        }

                                        if (service) {
                                            service.value = act.label;
                                        }

                                        if (baseAmount && amount) {
                                            let price = act.base;
                                            if ((option.coverage === 'CMU' || option.coverage === 'Assurance') && act.insured) {
                                                price = act.insured;
                                            }
                                            baseAmount.value = price;
                                            this.calculateAmount();
                                        }
                                    },
                                    calculateAmount() {
                                        const base = parseFloat(document.getElementById('base_amount').value) || 0;
                                        const taux = parseFloat(document.getElementById('taux').value) || 0;
                                        const amount = document.getElementById('amount');
                                        const remaining = base * (1 - taux / 100);
                                        amount.value = Math.round(remaining);
                                    }
                                }
                            }
                        </script>
                </x-ui.slide-over>
            @endif

            <a href="{{ route('caisse.historique') }}">
                <x-ui.button size="sm" variant="secondary">
                    <x-ui.icon name="archive" class="h-4 w-4" />
                    Historique
                </x-ui.button>
            </a>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-4">
        <x-ui.stat-card label="Paniers en cours" value="{{ $paniersEnCours }}" icon="clock" trend="Factures en cours" trend-tone="neutral" />
        <x-ui.stat-card label="A payer" value="{{ $aPayer }}" icon="banknotes" trend="En attente de paiement" trend-tone="warning" />
        <x-ui.stat-card label="Payees" value="{{ $payes }}" icon="check-circle" trend="Factures reglees" trend-tone="success" />
        <x-ui.stat-card
            :label="$isToday ? 'Journee en cours' : 'Journee cloturee'"
            :value="$isToday ? $openCount . ' ouvert(s)' : 'Tous soldes'"
            icon="{{ $isToday ? 'lock-open' : 'lock-closed' }}"
            :trend="$isToday ? $closedCount . ' deja cloture(s)' : 'Lecture seule'"
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
        </div>
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Patient</th>
                        <th class="px-5 py-3 font-semibold">Couverture</th>
                        <th class="px-5 py-3 font-semibold">Sous-total</th>
                        <th class="px-5 py-3 font-semibold">Assurance</th>
                        <th class="px-5 py-3 font-semibold">A payer</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold">Caissier</th>
                        <th class="px-5 py-3 font-semibold">Service</th>
                        <th class="px-5 py-3 font-semibold">Montant Total</th>
                        <th class="px-5 py-3 font-semibold">Mode de paiement</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($encaissements as $row)
                        <tr class="@if($row['is_paid']) bg-danger/5 @endif hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :initials="$row['initials']" size="sm" />
                                    <div>
                                        <p class="font-medium text-ink">{{ $row['patient_name'] }}</p>
                                        <p class="font-mono text-xs text-ink-muted">{{ $row['patient_code'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $row['coverage'] ?? 'Aucune' }}</td>
                            <td class="px-5 py-3.5 font-mono text-ink-soft">{{ $row['subtotal'] ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono text-success">{{ $row['insurance'] ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono font-semibold text-ink">{{ $row['a_payer'] ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col items-start gap-1">
                                    <x-ui.badge :tone="$row['status_tone']">{{ $row['status'] }}</x-ui.badge>
                                    @if ($row['type'] === 'panier')
                                        {{-- <x-ui.badge tone="brand">Panier</x-ui.badge> --}}
                                    @else
                                        <x-ui.badge tone="neutral">Encaissement</x-ui.badge>
                                    @endif
                                    @if ($row['closed'])
                                        <x-ui.badge tone="success">
                                            <x-ui.icon name="lock-closed" class="h-3 w-3" />
                                            Cloture {{ $row['closed_at'] }}
                                        </x-ui.badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $row['caissier'] ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $row['service'] ?? '—' }}</td>
                            <td class="px-5 py-3.5 font-mono font-medium text-ink">{{ $row['montant_total'] }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $row['method'] ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex justify-end gap-2">
                                    @if ($row['invoice_id'])
                                        <a href="{{ route('caisse.paniers.show', $row['invoice_id']) }}" class="rounded-md border border-line px-3 py-1.5 text-xs font-medium text-ink-soft hover:bg-white">
                                            Detail
                                        </a>
                                    @endif
                                    @if ($row['can_encaisser'] && $row['invoice_id'])
                                        {{-- L'encaissement exige un mode de paiement : sans ce
                                             champ, la validation le refuse et le bouton
                                             semble inopérant. Le montant est pré-rempli
                                             avec le solde restant, et reste modifiable
                                             pour un encaissement partiel. --}}
                                        <form method="POST" action="{{ route('caisse.paniers.pay', $row['invoice_id']) }}" class="flex flex-col items-end gap-1.5">
                                            @csrf
                                            <div class="flex items-center gap-1.5">
                                                <input
                                                    type="number"
                                                    name="amount"
                                                    min="1"
                                                    max="{{ $row['remaining_amount'] }}"
                                                    value="{{ old('amount', $row['remaining_amount']) }}"
                                                    aria-label="Montant à encaisser"
                                                    class="w-24 rounded-md border border-line bg-white py-1.5 px-2 text-right text-xs text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                                >
                                                <select
                                                    name="method"
                                                    aria-label="Mode de paiement"
                                                    class="rounded-md border border-line bg-white py-1.5 px-2 text-xs text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                                >
                                                    @foreach (array_keys(config('clinic.payment_methods')) as $method)
                                                        <option value="{{ $method }}">{{ $method }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <x-input-error :messages="$errors->get('method')" />
                                            <x-input-error :messages="$errors->get('amount')" />
                                            <button type="submit" class="self-end rounded-md bg-success px-3 py-1.5 text-xs font-medium text-white hover:bg-success/90">
                                                Encaisser
                                            </button>
                                        </form>
                                    @endif
                                    @if ($row['can_receipt'] && $row['payment_id'])
                                        <a href="{{ route('payments.receipt', $row['payment_id']) }}" target="_blank">
                                            <x-ui.button variant="secondary" size="sm">
                                                <x-ui.icon name="download" class="h-4 w-4" />
                                                Recu
                                            </x-ui.button>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-5 py-10 text-center text-sm text-ink-muted">
                                Aucun encaissement enregistre pour votre caisse ce jour.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

</x-layout.app-shell>
