<x-layout.app-shell title="Mon profil" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Mon profil</h1>
            <p class="mt-1 text-sm text-ink-muted">Gérez vos informations personnelles et votre mot de passe.</p>
        </div>
        <x-ui.badge tone="brand">{{ $userRole }}</x-ui.badge>
    </div>

    <div class="mt-6 flex max-w-2xl flex-col gap-6">
        <x-ui.card>
            @include('profile.partials.update-profile-information-form')
        </x-ui.card>

        <x-ui.card>
            @include('profile.partials.update-password-form')
        </x-ui.card>
    </div>

</x-layout.app-shell>
