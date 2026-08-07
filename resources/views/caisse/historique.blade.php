<x-layout.app-shell title="Historique Caisse" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Historique des journées</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Journées de caisse clôturées. Cliquez sur une journée pour consulter le détail des paiements.
            </p>
        </div>
        <a href="{{ route('caisse.index') }}">
            <x-ui.button variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4" />
                Retour à la caisse
            </x-ui.button>
        </a>
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Date</th>
                        <th class="px-5 py-3 font-semibold">Clôturée le</th>
                        <th class="px-5 py-3 font-semibold text-right">Transactions</th>
                        <th class="px-5 py-3 font-semibold text-right">Encaissé</th>
                        <th class="px-5 py-3 font-semibold text-right">Impayé</th>
                        <th class="px-5 py-3 font-semibold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($closedDays as $day)
                        @php
                            $dayDate = \Carbon\Carbon::parse($day->day);
                        @endphp
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="calendar" class="h-4 w-4 text-ink-muted" />
                                    <div>
                                        <p class="font-medium text-ink">{{ $dayDate->translatedFormat('l j F Y') }}</p>
                                        <p class="text-xs text-ink-muted">{{ $dayDate->translatedFormat('d/m/Y') }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">
                                {{ \Carbon\Carbon::parse($day->closed_at)->translatedFormat('d/m/Y H:i') }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-ink">
                                {{ $day->transactions_count }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-medium text-success">
                                {{ number_format($day->paid_amount, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-ink-soft">
                                {{ number_format($day->unpaid_amount ?? 0, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('caisse.jour', ['date' => $day->day]) }}">
                                    <x-ui.button size="sm" variant="secondary">
                                        <x-ui.icon name="eye" class="h-4 w-4" />
                                        Voir
                                    </x-ui.button>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <x-ui.icon name="archive" class="mx-auto h-8 w-8 text-ink-muted" />
                                <p class="mt-3 text-sm text-ink-muted">
                                    Aucune journée clôturée pour le moment.
                                </p>
                                <p class="mt-1 text-xs text-ink-muted">
                                    La clôture s'effectue depuis la page Caisse en fin de journée.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if ($closedDays->hasPages())
        <div class="mt-4">
            {{ $closedDays->links() }}
        </div>
    @endif

</x-layout.app-shell>
