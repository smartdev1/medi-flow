@php
    $currentUser = auth()->user();
    $pageTitle = 'Accès refusé — Clinique Saint Matthieu';
@endphp

<x-layout.app-shell
    title="Accès refusé"
    :userName="$currentUser?->name ?? 'Utilisateur'"
    :userRole="$currentUser?->role?->label() ?? 'Non connecté'"
    :userInitials="$currentUser ? strtoupper(mb_substr($currentUser->name, 0, 1)) : '?'"
    active=""
>

    <div class="flex flex-col items-center justify-center py-16 text-center sm:py-24">

        {{-- Icône verrou --}}
        <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-brand-50 sm:h-24 sm:w-24">
            <x-ui.icon name="lock" class="h-10 w-10 text-brand-500 sm:h-12 sm:w-12" />
        </div>

        {{-- Titre et message --}}
        <h1 class="mt-6 text-2xl font-bold text-ink sm:text-3xl">Accès refusé</h1>
        <p class="mt-3 max-w-md text-sm leading-relaxed text-ink-muted sm:text-base">
            Vous n'avez pas les permissions nécessaires pour accéder à cette page.
            Si vous pensez qu'il s'agit d'une erreur, contactez votre administrateur.
        </p>

        {{-- Bouton retour accueil --}}
        <a
            href="{{ route('accueil.index') }}"
            class="mt-8 inline-flex items-center gap-2 rounded-md bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
        >
            <x-ui.icon name="arrow-right" class="h-4 w-4 rotate-180" />
            Retour à l'accueil
        </a>

    </div>

</x-layout.app-shell>
