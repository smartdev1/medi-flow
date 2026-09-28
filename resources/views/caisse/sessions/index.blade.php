<x-layout.app-shell title="Sessions de caisse" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Sessions de caisse</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Historique de vos rondes de caisse avec fonds d'ouverture et de clôture.
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
                        <th class="px-5 py-3 font-semibold">Heure ouverture</th>
                        <th class="px-5 py-3 font-semibold">Heure fermeture</th>
                        <th class="px-5 py-3 font-semibold text-right">Fond ouverture</th>
                        <th class="px-5 py-3 font-semibold text-right">Fond fermeture</th>
                        <th class="px-5 py-3 font-semibold text-right">Écart</th>
                        <th class="px-5 py-3 font-semibold text-right">Transactions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($sessions as $session)
                        @php
                            $variance = $session->cashVariance();
                            $varianceTone = match (true) {
                                $variance === null => 'neutral',
                                $variance === 0 => 'success',
                                $variance > 0 => 'warning',
                                default => 'danger',
                            };
                            $varianceLabel = $variance !== null
                                ? ($variance > 0 ? '+'.number_format($variance, 0, ',', ' ') : number_format($variance, 0, ',', ' '))
                                : '—';
                        @endphp
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.icon name="calendar" class="h-4 w-4 text-ink-muted" />
                                    <span class="font-medium text-ink">{{ $session->opened_at->translatedFormat('l j F Y') }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $session->opened_at->format('H:i') }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">
                                {{ $session->closed_at?->format('H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-ink">
                                {{ number_format($session->opening_balance, 0, ',', ' ') }} FCFA
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-ink">
                                {{ $session->closing_balance !== null ? number_format($session->closing_balance, 0, ',', ' ').' FCFA' : '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-medium">
                                <span class="text-{{ $varianceTone }}">
                                    {{ $varianceLabel }} FCFA
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right text-ink">
                                {{ $session->payments_count }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('caisse.sessions.show', $session) }}">
                                    <x-ui.button size="sm" variant="secondary">
                                        <x-ui.icon name="eye" class="h-4 w-4" />
                                        Détail
                                    </x-ui.button>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <x-ui.icon name="archive" class="mx-auto h-8 w-8 text-ink-muted" />
                                <p class="mt-3 text-sm text-ink-muted">
                                    Aucune session de caisse enregistrée.
                                </p>
                                <p class="mt-1 text-xs text-ink-muted">
                                    Ouvrez une session depuis la page Caisse pour commencer.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if ($sessions->hasPages())
        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
    @endif

</x-layout.app-shell>
