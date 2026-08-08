<section>
    <header>
        <h2 class="text-sm font-semibold text-ink">Mot de passe</h2>
        <p class="mt-1 text-sm text-ink-muted">Utilisez un mot de passe long et unique pour sécuriser votre compte.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-4">
        @csrf
        @method('put')

        <div>
            <x-ui.field label="Mot de passe actuel" name="current_password" type="password" icon="lock" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div>
            <x-ui.field label="Nouveau mot de passe" name="password" type="password" icon="lock" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div>
            <x-ui.field label="Confirmer le nouveau mot de passe" name="password_confirmation" type="password" icon="lock" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="mt-2 flex justify-end border-t border-line pt-4">
            <x-ui.button type="submit">Mettre à jour le mot de passe</x-ui.button>
        </div>
    </form>
</section>
