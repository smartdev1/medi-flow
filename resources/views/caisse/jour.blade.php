<x-layout.app-shell title="Journée de caisse" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-semibold text-ink">{{ $currentDate->translatedFormat('l j F Y') }}</h1>
                @if ($sessions->contains(fn ($session) => $session->isActive()))
                    <x-ui.badge tone="warning">
                        <x-ui.icon name="lock-open" class="h-3 w-3" />
                        Vacation en cours
                    </x-ui.badge>
                @else
                    <x-ui.badge tone="success">
                        <x-ui.icon name="lock-closed" class="h-3 w-3" />
                        Vacations clôturées
                    </x-ui.badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-ink-muted">
                La journée se lit à travers les vacations : une vacation de 22h à 6h encaisse
                après minuit sans être amputée de ses encaissements.
            </p>
        </div>
        <a href="{{ route('caisse.historique') }}">
            <x-ui.button variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4" />
                Retour à l'historique
            </x-ui.button>
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat-card
            label="Total encaissé"
            value="{{ number_format($totalAmount, 0, ',', ' ') }} FCFA"
            icon="credit-card"
            trend="Sur les vacations du jour"
            trend-tone="success"
        />
        <x-ui.stat-card
            label="Vacations"
            value="{{ $sessions->count() }}"
            icon="clock"
            trend="Ouvertes et clôturées"
            trend-tone="neutral"
        />
        <x-ui.stat-card
            label="Versé en caisse"
            value="{{ number_format($versements->sum('amount'), 0, ',', ' ') }} FCFA"
            icon="arrow-up-right"
            trend="{{ $versements->count() }} versement(s)"
            trend-tone="info"
        />
    </div>

    <x-ui.card class="mt-6" :padded="false">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">Vacations</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Ouverture</th>
                        <th class="px-5 py-3 font-semibold">Caissier</th>
                        <th class="px-5 py-3 font-semibold text-right">Encaissé</th>
                        <th class="px-5 py-3 font-semibold text-right">Clôture</th>
                        <th class="px-5 py-3 font-semibold text-right">Écart</th>
                        <th class="px-5 py-3 font-semibold">État</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($sessions as $session)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5 font-mono text-xs text-ink-muted">
                                {{ $session->opened_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3.5 text-ink">{{ $session->cashier?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-right font-mono font-medium text-ink">
                                {{ number_format($session->settledAmount(), 0, ',', ' ') }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-ink-soft">
                                {{ $session->isClosed() ? number_format($session->closing_balance, 0, ',', ' ') : '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-ink-soft">
                                @php $variance = $session->cashVariance(); @endphp
                                @if ($variance === null)
                                    —
                                @elseif ($variance === 0)
                                    <span class="text-success">0</span>
                                @else
                                    <span class="{{ $variance > 0 ? 'text-warning' : 'text-danger' }}">
                                        {{ number_format($variance, 0, ',', ' ') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($session->isActive())
                                    <x-ui.badge tone="warning">En cours</x-ui.badge>
                                @else
                                    <x-ui.badge tone="success">Clôturée</x-ui.badge>
                                    @if ($session->transferredAmount() > 0)
                                        <p class="mt-1 text-xs text-ink-muted">
                                            Relève : {{ number_format($session->transferredAmount(), 0, ',', ' ') }} FCFA
                                        </p>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-ink-muted">
                                Aucune vacation ouverte cette date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card class="mt-6" :padded="false">
        <div class="border-b border-line px-5 py-4">
            <h2 class="text-sm font-semibold text-ink">Versements à la caisse centrale</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Déposé le</th>
                        <th class="px-5 py-3 font-semibold">Caissier</th>
                        <th class="px-5 py-3 font-semibold">Mode</th>
                        <th class="px-5 py-3 font-semibold">Référence</th>
                        <th class="px-5 py-3 font-semibold text-right">Montant</th>
                        <th class="px-5 py-3 font-semibold">Validation</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($versements as $versement)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5 font-mono text-xs text-ink-muted">
                                {{ $versement->deposited_at?->format('d/m/Y H:i') ?? $versement->date?->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-3.5 text-ink">{{ $versement->cashier?->name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $versement->method }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $versement->reference ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-right font-mono font-medium text-ink">
                                {{ $versement->formatted_amount }}
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($versement->isValidated())
                                    <x-ui.badge tone="success">Validé</x-ui.badge>
                                @else
                                    <x-ui.badge tone="warning">À valider</x-ui.badge>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-ink-muted">
                                Aucun versement enregistré cette date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

</x-layout.app-shell>
