<x-layout.app-shell title="Clôture d'urgence" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Clôture d'urgence de session</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Cette action ferme la session sans comptage de caisse. Un motif obligatoire est requis.
            </p>
        </div>
        <a href="{{ route('caisse.sessions.index') }}">
            <x-ui.button variant="secondary" size="sm">
                <x-ui.icon name="arrow-left" class="h-4 w-4" />
                Annuler
            </x-ui.button>
        </a>
    </div>

    <div class="mt-6 rounded-lg border border-danger/30 bg-danger/5 p-4">
        <div class="flex items-center gap-3">
            <x-ui.icon name="alert-triangle" class="h-5 w-5 text-danger" />
            <div>
                <p class="font-medium text-danger">Clôture d'urgence</p>
                <p class="text-sm text-ink-muted">Aucun fond de clôture ne sera saisi. La session sera marquée en attente d'audit.</p>
            </div>
        </div>
    </div>

    <div class="mt-6 max-w-lg">
        <x-ui.card>
            <div class="mb-5 rounded-md bg-surface-sunken p-3">
                <p class="text-sm text-ink-muted">Session de {{ $session->cashier->name }}</p>
                <p class="text-sm text-ink-muted">Ouverte le {{ $session->opened_at->translatedFormat('l j F Y à H:i') }}</p>
                <p class="text-lg font-semibold text-ink">Fond d'ouverture : {{ number_format($session->opening_balance, 0, ',', ' ') }} FCFA</p>
            </div>

            <div class="mb-5 rounded-md border border-warning/20 bg-warning/5 p-3">
                <p class="text-sm text-ink-muted">Fond théorique (référence)</p>
                <p class="text-lg font-semibold text-ink">{{ number_format($session->theoreticalBalance(), 0, ',', ' ') }} FCFA</p>
                <p class="text-xs text-ink-muted mt-1">Ce montant sera vérifié lors de l'audit.</p>
            </div>

            <form method="POST" action="{{ route('caisse.sessions.emergency-close', $session) }}" class="flex flex-col gap-5">
                @csrf

                <div>
                    <x-ui.field
                        label="Motif de la clôture d'urgence *"
                        name="emergency_closure_reason"
                        id="emergency_closure_reason"
                        type="textarea"
                        placeholder="Raison de la clôture d'urgence (ex: caissier absent, urgence médicale...)"
                        required
                    />
                    <x-input-error :messages="$errors->get('emergency_closure_reason')" class="mt-1" />
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="danger">
                        <x-ui.icon name="lock-closed" class="h-4 w-4" />
                        Fermer en urgence
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

</x-layout.app-shell>
