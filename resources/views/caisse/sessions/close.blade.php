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

            <form method="POST" action="{{ route('caisse.sessions.close') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-ui.field
                        label="Fond de clôture (FCFA)"
                        name="closing_balance"
                        id="closing_balance"
                        type="number"
                        placeholder="0"
                        :value="old('closing_balance')"
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

</x-layout.app-shell>
