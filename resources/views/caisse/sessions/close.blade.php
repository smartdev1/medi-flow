<x-layout.app-shell title="Fermer la session" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Fermer la session de caisse</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Déclarez le fond de caisse restant à la fin de votre ronde.
            </p>
        </div>
        <a href="{{ route('caisse.index') }}">
            <x-ui.button variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4" />
                Annuler
            </x-ui.button>
        </a>
    </div>

    @if ($openPaymentsCount > 0)
        <div class="mt-6 rounded-lg border border-warning/30 bg-warning/5 p-4">
            <div class="flex items-center gap-3">
                <x-ui.icon name="alert-triangle" class="h-5 w-5 text-warning" />
                <div>
                    <p class="font-medium text-warning">
                        Vous avez {{ $openPaymentsCount }} paiement(s) en cours
                    </p>
                    <p class="text-sm text-ink-muted">
                        Certains paiements n'ont pas été clôturés individuellement. La fermeture de session est toujours possible.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-6 max-w-lg">
        <x-ui.card>
            <div class="mb-5 rounded-md bg-surface-sunken p-3">
                <p class="text-sm text-ink-muted">Fond d'ouverture</p>
                <p class="text-lg font-semibold text-ink">{{ number_format($session->opening_balance, 0, ',', ' ') }} FCFA</p>
            </div>

            @if (isset($theoreticalBalance))
                <div class="mb-5 rounded-md border border-success/20 bg-success/5 p-3">
                    <p class="text-sm text-ink-muted">Fond théorique</p>
                    <p class="text-lg font-semibold text-success">{{ number_format($theoreticalBalance, 0, ',', ' ') }} FCFA</p>
                </div>
            @endif

            <form method="POST" action="{{ route('caisse.sessions.close') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-ui.field
                        label="Fond de clôture (FCFA)"
                        name="closing_balance"
                        id="closing_balance"
                        type="number"
                        placeholder="0"
                        :value="old('closing_balance', $theoreticalBalance ?? null)"
                        min="0"
                        required
                    />
                    <p class="mt-1 text-xs text-ink-muted">Montant en espèces restant en caisse à la fin de la session.</p>
                    <x-input-error :messages="$errors->get('closing_balance')" class="mt-1" />
                </div>

                <div>
                    <x-ui.field
                        label="Notes (optionnel)"
                        name="notes"
                        id="notes"
                        type="textarea"
                        placeholder="Observations, écart constaté, etc."
                        :value="old('notes', $session->notes)"
                    />
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="danger">
                        <x-ui.icon name="lock-closed" class="h-4 w-4" />
                        Fermer la session
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    @if (isset($settledPayments) && $settledPayments->count() > 0)
        <div class="mt-6 max-w-2xl">
            <h2 class="text-lg font-semibold text-ink mb-3">Encaissements de la session</h2>
            <div class="overflow-x-auto rounded-lg border border-border bg-white">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-ink-muted">Patient</th>
                            <th class="px-4 py-2 text-left font-medium text-ink-muted">Acte</th>
                            <th class="px-4 py-2 text-right font-medium text-ink-muted">Montant</th>
                            <th class="px-4 py-2 text-left font-medium text-ink-muted">Méthode</th>
                            <th class="px-4 py-2 text-left font-medium text-ink-muted">Heure</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach ($settledPayments as $payment)
                            <tr>
                                <td class="px-4 py-2">{{ $payment->patient->full_name ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $payment->act->label ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-right">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-2">{{ $payment->method }}</td>
                                <td class="px-4 py-2">{{ $payment->created_at?->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @elseif (isset($settledPayments))
        <div class="mt-6 max-w-lg">
            <p class="text-sm text-ink-muted italic">Aucun encaissement cette session.</p>
        </div>
    @endif

</x-layout.app-shell>
