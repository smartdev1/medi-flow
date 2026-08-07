<x-layout.app-shell title="Journée de caisse" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-semibold text-ink">{{ $currentDate->translatedFormat('l j F Y') }}</h1>
                @if ($isClosed)
                    <x-ui.badge tone="success">
                        <x-ui.icon name="lock-closed" class="h-3 w-3" />
                        Journée clôturée
                    </x-ui.badge>
                @else
                    <x-ui.badge tone="warning">
                        <x-ui.icon name="lock-open" class="h-3 w-3" />
                        Journée ouverte
                    </x-ui.badge>
                @endif
            </div>
            <p class="mt-1 text-sm text-ink-muted">
                Détail des paiements enregistrés le {{ $currentDate->translatedFormat('d/m/Y') }}.
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
        <x-ui.stat-card label="Total encaissé" value="{{ number_format($totalAmount, 0, ',', ' ') }} FCFA" icon="credit-card" trend="Paiements validés" trend-tone="success" />
        <x-ui.stat-card label="Transactions" value="{{ $payments->count() }}" icon="list" trend="Sur la journée" trend-tone="neutral" />
        <x-ui.stat-card label="Impayés" value="{{ $pendingCount }}" icon="alert-triangle" trend="À suivre" trend-tone="danger" />
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Heure</th>
                        <th class="px-5 py-3 font-semibold">Patient</th>
                        <th class="px-5 py-3 font-semibold">Service</th>
                        <th class="px-5 py-3 font-semibold">Montant</th>
                        <th class="px-5 py-3 font-semibold">Mode</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($payments as $payment)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5 font-mono text-xs text-ink-muted">
                                {{ $payment->created_at->format('H:i') }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :initials="$payment->patient->initials" size="sm" />
                                    <div>
                                        <p class="font-medium text-ink">{{ $payment->patient->full_name }}</p>
                                        <p class="font-mono text-xs text-ink-muted">{{ $payment->patient->code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $payment->service }}</td>
                            <td class="px-5 py-3.5 font-mono font-medium text-ink">{{ $payment->formatted_amount }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $payment->method }}</td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$payment->status_tone">{{ $payment->status }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if($payment->status === 'Payé')
                                    <a href="{{ route('payments.receipt', $payment->id) }}" target="_blank" class="inline-flex items-center rounded-md border border-line bg-white px-3 py-2 text-xs font-medium text-ink hover:bg-surface-muted">
                                        Imprimer
                                    </a>
                                @else
                                    <span class="text-xs text-ink-muted">Non disponible</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-sm text-ink-muted">
                                Aucun paiement enregistré pour cette date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

</x-layout.app-shell>
