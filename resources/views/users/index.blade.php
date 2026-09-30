<x-layout.app-shell title="Gestion utilisateur" active="gestion" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Gestion utilisateur</h1>
            <p class="mt-1 text-sm text-ink-muted">Créer, modifier, activer ou désactiver les comptes utilisateurs avec leurs rôles.</p>
        </div>

    @if (auth()->user()?->isAdmin())
    <x-ui.slide-over title="Nouvel utilisateur">
        <x-slot:trigger>
            <x-ui.button>
                <x-ui.icon name="user-plus" class="h-4 w-4" />
                Nouveau
            </x-ui.button>
        </x-slot:trigger>

        <form method="POST" action="{{ route('users.store') }}" class="flex flex-col gap-4">
            @csrf

            <div>
                <x-ui.field label="Nom complet" name="name" :value="old('name')" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>

            <div>
                <x-ui.field label="Adresse e-mail" name="email" type="email" icon="mail" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div>
                <x-ui.select label="Rôle" name="role" placeholder="Sélectionner" :options="collect($roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all()" required />
                <x-input-error :messages="$errors->get('role')" class="mt-1" />
            </div>

            <p class="text-xs text-ink-muted">Un mot de passe temporaire sera généré automatiquement et affiché après la création du compte.</p>

            <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                <x-ui.button type="submit" class="w-full justify-center">Enregistrer</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
    @endif
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Nom</th>
                        <th class="px-5 py-3 font-semibold">Email</th>
                        <th class="px-5 py-3 font-semibold">Rôle</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($users as $user)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5 font-medium text-ink">{{ $user->name }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $user->email }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $user->role?->label() ?? $user->role }}</td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$user->is_active ? 'success' : 'neutral'">{{ $user->is_active ? 'Actif' : 'Désactivé' }}</x-ui.badge>
                            </td>
                            @if (auth()->user()?->isAdmin())
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('users.index', ['edit_user_id' => $user->id]) }}" class="inline-flex items-center rounded-md border border-line px-3 py-1.5 text-sm font-semibold text-ink-soft hover:bg-surface-muted">
                                        Modifier
                                    </a>
                                    <form method="POST" action="{{ route('users.toggle', $user) }}">
                                        @csrf
                                        <x-ui.button variant="secondary" size="sm" type="submit">
                                            {{ $user->is_active ? 'Désactiver' : 'Réactiver' }}
                                        </x-ui.button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @if ($editingUser && auth()->user()?->isAdmin())
        <x-ui.card class="mt-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-ink">Modifier un utilisateur</h3>
                    <p class="mt-1 text-sm text-ink-muted">Mettez à jour les informations du compte sélectionné.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('users.update', $editingUser) }}" class="mt-4 grid gap-4 lg:grid-cols-2">
                @csrf
                @method('PUT')

                <div>
                    <x-ui.field label="Nom complet" name="name" :value="old('name', $editingUser->name)" required />
                </div>
                <div>
                    <x-ui.field label="Adresse e-mail" name="email" type="email" icon="mail" :value="old('email', $editingUser->email)" required />
                </div>
                <div>
                    <x-ui.select label="Rôle" name="role" placeholder="Sélectionner" :options="collect($roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all()" :selected="old('role', $editingUser->role?->value ?? $editingUser->role)" required />
                </div>

                <div class="flex items-end gap-2 lg:col-span-2">
                    <x-ui.button type="submit">Enregistrer</x-ui.button>
                    <a href="{{ route('users.index') }}" class="inline-flex items-center rounded-md border border-line px-3 py-2 text-sm font-semibold text-ink-soft hover:bg-surface-muted">
                        Annuler
                    </a>
                </div>
            </form>
        </x-ui.card>
    @endif
</x-layout.app-shell>
