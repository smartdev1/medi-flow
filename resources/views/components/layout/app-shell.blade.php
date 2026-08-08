@props([
    'title' => 'Clinique Saint Matthieu',
    'active' => null,
    'userName' => 'Utilisateur',
    'userRole' => 'Personnel clinique',
    'userInitials' => 'U',
])

@php
    $currentUser = auth()->user();

    $nav = collect([
        'accueil' => ['label' => 'Accueil', 'icon' => 'user-plus', 'href' => route('accueil.index'), 'roles' => ['accueil'], 'active_routes' => ['accueil.index']],
        'caisse' => ['label' => 'Caisse', 'icon' => 'credit-card', 'href' => route('caisse.index'), 'roles' => ['caissier'], 'active_routes' => ['caisse.index']],
        'attente' => ['label' => "Salle d'attente", 'icon' => 'users', 'href' => route('attente.index'), 'roles' => ['accueil'], 'active_routes' => ['attente.index']],
        'medecin' => ['label' => 'Médecin', 'icon' => 'stethoscope', 'href' => route('medecin.index'), 'roles' => ['medecin', 'pediatre'], 'active_routes' => ['medecin.index']],
        'laboratoire' => ['label' => 'Laboratoire', 'icon' => 'beaker', 'href' => route('laboratoire.index'), 'roles' => ['laborantin'], 'active_routes' => ['laboratoire.index']],
        'pharmacie' => ['label' => 'Pharmacie', 'icon' => 'capsule', 'href' => route('pharmacie.index'), 'roles' => ['pharmacien'], 'active_routes' => ['pharmacie.index']],
        'hospitalisation' => ['label' => 'Hospitalisation', 'icon' => 'building', 'href' => route('hospitalisation.index'), 'roles' => ['medecin', 'pediatre'], 'active_routes' => ['hospitalisation.index']],
        'chambres' => ['label' => 'Chambres', 'icon' => 'bed', 'href' => route('chambres.index'), 'roles' => ['medecin', 'pediatre'], 'active_routes' => ['chambres.index']],
        'sortie' => ['label' => 'Sortie patient', 'icon' => 'check-circle', 'href' => route('sortie.index'), 'roles' => ['accueil', 'caissier'], 'active_routes' => ['sortie.index']],
        'logistique' => ['label' => 'Logistique', 'icon' => 'archive-box', 'href' => route('logistique.index'), 'roles' => ['logistique'], 'active_routes' => ['logistique.index']],
        'gestion' => [
            'label' => 'Gestion clinique',
            'icon' => 'briefcase',
            'href' => route('patients.index'),
            'roles' => ['accueil'],
            'active_routes' => ['patients.index', 'insurance.index', 'users.index', 'insurance-companies.store'],
            'children' => [
                ['label' => 'Compagnies d’assurance', 'icon' => 'shield-check', 'href' => '/insurance', 'route' => 'insurance.index'],
                ['label' => 'Liste patients', 'icon' => 'users', 'href' => route('patients.index'), 'route' => 'patients.index'],
                ['label' => 'Gestion utilisateur', 'icon' => 'user-group', 'href' => '/users', 'route' => 'users.index'],
            ],
        ],
        'personnel' => ['label' => 'Personnel médical', 'icon' => 'user-plus', 'href' => route('personnel.index'), 'roles' => [], 'active_routes' => ['personnel.index']],
    ])->filter(fn ($item) => $currentUser && ($currentUser->isAdmin() || in_array($currentUser->role->value, $item['roles'], true)));
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Clinique Saint Matthieu</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink antialiased bg-surface-sunken">
    <div class="flex min-h-screen" x-data="{ mobileNavOpen: false }">
        <aside class="hidden w-64 shrink-0 flex-col border-r border-line bg-white lg:flex">
            <x-layout.sidebar-nav :nav="$nav" :active="$active" />
        </aside>

        {{-- Tiroir de navigation mobile / tablette --}}
        <div class="fixed inset-0 z-40 lg:hidden" x-show="mobileNavOpen" x-cloak>
            <div
                class="absolute inset-0 bg-ink/40"
                x-show="mobileNavOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="mobileNavOpen = false"
            ></div>

            <div
                class="absolute left-0 top-0 flex h-full w-72 max-w-[80vw] flex-col bg-white shadow-xl"
                x-show="mobileNavOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="-translate-x-full"
                x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-x-0"
                x-transition:leave-end="-translate-x-full"
            >
                <button type="button" aria-label="Fermer le menu" @click="mobileNavOpen = false" class="absolute right-3 top-3 rounded-md p-1.5 text-ink-muted hover:bg-surface-muted">
                    <x-ui.icon name="x-circle" class="h-5 w-5" />
                </button>
                <x-layout.sidebar-nav :nav="$nav" :active="$active" />
            </div>
        </div>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 shrink-0 items-center gap-3 border-b border-line bg-white px-4 sm:px-6">
                <button type="button" aria-label="Ouvrir le menu" @click="mobileNavOpen = true" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-ink-soft hover:bg-surface-muted lg:hidden">
                    <x-ui.icon name="menu" class="h-5 w-5" />
                </button>

                <div class="relative w-full min-w-0 sm:max-w-sm">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
                        <x-ui.icon name="search" class="h-4 w-4" />
                    </span>
                    <input type="text" placeholder="Rechercher..."
                           class="w-full rounded-md border border-line bg-surface-sunken py-2 pl-9 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>

                <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-2">
                    <button type="button" class="relative flex h-9 w-9 items-center justify-center rounded-md text-ink-soft hover:bg-surface-muted">
                        <x-ui.icon name="bell" class="h-5 w-5" />
                        <span class="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-danger"></span>
                    </button>

                    <div class="mx-2 h-6 w-px bg-line"></div>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <div class="flex items-center gap-2.5 py-1 pl-1 pr-2">
                                <x-ui.avatar :initials="$userInitials" size="sm" />
                                <div class="hidden text-left leading-tight sm:block">
                                    <p class="text-sm font-semibold text-ink">{{ $userName }}</p>
                                    <p class="text-xs text-ink-muted">{{ $userRole }}</p>
                                </div>
                                <x-ui.icon name="chevron-down" class="hidden h-4 w-4 text-ink-muted sm:block" />
                            </div>
                        </x-slot:trigger>

                        <div class="border-b border-line px-4 py-3">
                            <p class="text-sm font-semibold text-ink">{{ $userName }}</p>
                            <p class="text-xs text-ink-muted">{{ $userRole }}</p>
                        </div>

                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-ink-soft hover:bg-surface-sunken">
                            <x-ui.icon name="cog" class="h-4 w-4" />
                            Paramètres du compte
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 border-t border-line px-4 py-2.5 text-left text-sm text-danger hover:bg-danger-tint">
                                <x-ui.icon name="logout" class="h-4 w-4" />
                                Se déconnecter
                            </button>
                        </form>
                    </x-ui.dropdown>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 sm:px-6 sm:py-8">
                <div class="mx-auto max-w-7xl">
                    @if (session('success'))
                        <div class="mb-6 flex items-center gap-2 rounded-md border border-success/20 bg-success-tint px-4 py-3 text-sm text-success">
                            <x-ui.icon name="check-circle" class="h-4 w-4 shrink-0" />
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 flex items-center gap-2 rounded-md border border-danger/20 bg-danger-tint px-4 py-3 text-sm text-danger">
                            <x-ui.icon name="alert-triangle" class="h-4 w-4 shrink-0" />
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
