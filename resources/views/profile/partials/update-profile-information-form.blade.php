<section>
    <header>
        <h2 class="text-sm font-semibold text-ink">Informations personnelles</h2>
        <p class="mt-1 text-sm text-ink-muted">Mettez à jour votre nom et votre adresse e-mail professionnelle.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 flex flex-col gap-4">
        @csrf
        @method('patch')

        <div>
            <x-ui.field label="Nom complet" name="name" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-ui.field label="Adresse e-mail" name="email" type="email" icon="mail" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <label class="text-sm font-medium text-ink">Rôle</label>
            <div class="mt-1.5 flex items-center gap-2 rounded-md border border-line bg-surface-sunken px-3 py-2.5 text-sm text-ink-muted">
                <x-ui.icon name="shield-check" class="h-4 w-4" />
                {{ $user->role->label() }}
                <span class="ml-auto text-xs">Attribué par un administrateur</span>
            </div>
        </div>

        <div class="mt-2 flex justify-end border-t border-line pt-4">
            <x-ui.button type="submit">Enregistrer les modifications</x-ui.button>
        </div>
    </form>
</section>
