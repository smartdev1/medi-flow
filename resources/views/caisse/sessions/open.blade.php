<x-layout.app-shell title="Ouvrir une session" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Ouvrir une session de caisse</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Déclarez le fond de caisse initial disponible au début de votre ronde.
            </p>
        </div>
        <a href="{{ route('caisse.index') }}">
            <x-ui.button variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4" />
                Annuler
            </x-ui.button>
        </a>
    </div>

    <div class="mt-6 max-w-lg">
        <x-ui.card>
            <form method="POST" action="{{ route('caisse.sessions.open') }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-ui.field
                        label="Fond de caisse initial (FCFA)"
                        name="opening_balance"
                        id="opening_balance"
                        type="number"
                        placeholder="0"
                        :value="old('opening_balance')"
                        min="0"
                        required
                    />
                    <p class="mt-1 text-xs text-ink-muted">Montant en espèces disponible au début de la session.</p>
                    <x-input-error :messages="$errors->get('opening_balance')" class="mt-1" />
                </div>

                <div>
                    <x-ui.field
                        label="Notes (optionnel)"
                        name="notes"
                        id="notes"
                        type="textarea"
                        placeholder="Observations sur le fond de caisse, etc."
                        :value="old('notes')"
                    />
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit">
                        <x-ui.icon name="lock-open" class="h-4 w-4" />
                        Ouvrir la session
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

</x-layout.app-shell>
