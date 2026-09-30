<x-layout.app-shell title="Détail session" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Détail de la session</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Session du {{ $session->opened_at->translatedFormat('l j F Y') }} — Ouverte à {{ $session->opened_at->format('H:i') }}
            </p>
        </div>
        <a href="{{ route('caisse.sessions.index') }}">
            <x-ui.button variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4" />
                Retour aux sessions
            </x-ui.button>
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="Fond d'ouverture"
            :value="number_format($session->opening_balance, 0, ',', ' ').' FCFA'"
            icon="banknotes"
            trend="Fonds initiaux"
            trend-tone="neutral"
        />
        <x-ui.stat-card
            label="Fond de clôture"
            :value="$session->closing_balance !== null ? number_format($session->closing_balance, 0, ',', ' ').' FCFA' : 'Non clôturé'"
            icon="lock-closed"
            trend="Fonds finaux"
            trend-tone="{{ $session->isActive() ? 'warning' : 'success' }}"
        />
        <x-ui.stat-card
            label="Écart de caisse"
            :value="$variance !== null ? number_format($variance, 0, ',', ' ').' FCFA' : 'N/A'"
            icon="scale"
            trend="{{ $variance !== null ? ($variance === 0 ? 'Aucun écart' : ($variance > 0 ? 'Excédent' : 'Manquant')) : 'Session ouverte' }}"
            trend-tone="{{ match(true) { $variance === null => 'neutral', $variance === 0 => 'success', $variance > 0 => 'warning', default => 'danger' } }}"
        />
        <x-ui.stat-card
            label="Durée"
            :value="$duration ? $duration->forHumans() : 'En cours'"
            icon="clock"
            trend="Temps de la session"
            trend-tone="neutral"
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-ink-soft uppercase tracking-wide">Caissier</h3>
            <p class="mt-2 text-ink">{{ $session->cashier->name }}</p>
            <p class="text-sm text-ink-muted">{{ $session->cashier->email }}</p>
        </x-ui.card>
        <x-ui.card>
            <h3 class="text-sm font-semibold text-ink-soft uppercase tracking-wide">Notes</h3>
            <p class="mt-2 text-sm text-ink">{{ $session->notes ?: 'Aucune note.' }}</p>
        </x-ui.card>
    </div>

    <div class="mt-6">
        <h2 class="text-lg font-semibold text-ink">Transactions de la session ({{ $session->payments_count }})</h2>

        <x-ui.card :padded="false" class="mt-4 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                            <th class="px-5 py-3 font-semibold">Date</th>
                            <th class="px-5 py-3 font-semibold">Patient</th>
                            <th class="px-5 py-3 font-semibold">Service</th>
                            <th class="px-5 py-3 font-semibold text-right">Montant</th>
                            <th class="px-5 py-3 font-semibold">Mode</th>
                            <th class="px-5 py-3 font-semibold">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($session->payments as $payment)
                            <tr class="hover:bg-surface-sunken">
                                <td class="px-5 py-3.5 text-ink-soft">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :initials="$payment->patient->initials" size="sm" />
                                        <div>
                                            <p class="font-medium text-ink">{{ $payment->patient->full_name }}</p>
                                            <p class="text-xs text-ink-muted">{{ $payment->patient->code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-ink-soft">{{ $payment->service }}</td>
                                <td class="px-5 py-3.5 text-right font-mono font-medium text-ink">{{ $payment->formatted_amount }}</td>
                                <td class="px-5 py-3.5 text-ink-soft">{{ $payment->method }}</td>
                                <td class="px-5 py-3.5">
                                    <x-ui.badge :tone="$payment->status_tone">{{ $payment->status }}</x-ui.badge>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-ink-muted">
                                    Aucune transaction dans cette session.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

</x-layout.app-shell>
