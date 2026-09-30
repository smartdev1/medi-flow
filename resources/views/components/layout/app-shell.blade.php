@props([
    'title' => 'Clinique Saint Matthieu',
    'active' => null,
    'userName' => 'Utilisateur',
    'userRole' => 'Personnel clinique',
    'userInitials' => 'U',
])

@php
    $currentUser = auth()->user();

    $unpaidOutflowInvoices = \App\Models\Invoice::where('status', '!=', \App\Models\Invoice::STATUS_PAID)
        ->whereHas('visit', fn($q) => $q->where('status', 'Consultation terminée')->whereNull('discharged_at'))
        ->count();

    $badgeCounts = [
        'caisse' => \App\Models\Invoice::whereIn('status', [\App\Models\Invoice::STATUS_PENDING, \App\Models\Invoice::STATUS_AWAITING_PAYMENT])->whereDate('created_at', today())->count(),
        'attente' => \App\Models\Visit::whereNull('discharged_at')->whereDate('arrived_at', today())->where('status', \App\Models\Visit::STATUS_WAITING)->whereNull('vitals_taken_at')->count(),
        'medecin' => \App\Models\Visit::whereNull('discharged_at')->whereDate('arrived_at', today())->whereIn('status', [\App\Models\Visit::STATUS_WAITING, \App\Models\Visit::STATUS_RETURNED_FROM_OBSERVATION])->whereNotNull('vitals_taken_at')->count(),
        'laboratoire' => \App\Models\Exam::whereIn('status', [\App\Models\Exam::STATUS_AWAITING_SAMPLE, \App\Models\Exam::STATUS_IN_PROGRESS])->count(),
        'hospitalisation' => \App\Models\AdmissionRequest::pending()->count() + \App\Models\Admission::active()->where('status', \App\Models\Admission::STATUS_STABLE)->count() + \App\Models\Admission::where('status', \App\Models\Admission::STATUS_OBSERVATION)->active()->count(),
        'sortie' => \App\Models\Visit::where('status', 'Consultation terminée')->whereNull('discharged_at')->count(),
        'observation' => \App\Models\Admission::where('status', \App\Models\Admission::STATUS_OBSERVATION)->whereNull('discharged_at')->count(),
    ];

    $nav = collect([
        // ── Zone 0 : Supervision ──────────────────────────────────────────────
        // La vue d'ensemble est l'écran d'arrivée du docteur général : sans
        // cette entrée, il y atterrissait mais ne pouvait plus y revenir.
        [
            'type' => 'section',
            'label' => 'Supervision',
            'items' => [
                'supervision' => ['label' => "Vue d'ensemble", 'icon' => 'grid', 'href' => route('docteurgeneral.index'), 'roles' => ['docteur_general'], 'active_routes' => ['docteurgeneral.index']],
            ],
        ],

        // ── Zone 1 : Parcours Patient ──────────────────────────────────────────
        // Les rôles reprennent exactement ceux des middlewares de route : un
        // lien affiché à un rôle qui n'a pas la route ne mène qu'à un 403.
        [
            'type' => 'section',
            'label' => 'Parcours Patient',
            'items' => [
                'accueil'     => ['label' => 'Accueil',          'icon' => 'user-plus',     'href' => route('accueil.index'),     'roles' => ['accueil', 'docteur_general', 'aide_soignante'],         'active_routes' => ['accueil.index']],
                'attente'     => ['label' => "Salle d'attente",   'icon' => 'users',         'href' => route('attente.index'),     'roles' => ['accueil', 'docteur_general', 'aide_soignante'],         'active_routes' => ['attente.index']],
                'medecin'     => ['label' => 'Médecin',           'icon' => 'stethoscope',   'href' => route('medecin.index'),     'roles' => ['medecin', 'pediatre', 'docteur_general'],         'active_routes' => ['medecin.index']],
                'laboratoire' => ['label' => 'Laboratoire',       'icon' => 'beaker',        'href' => route('laboratoire.index'), 'roles' => ['laborantin', 'docteur_general'],                       'active_routes' => ['laboratoire.index']],
                'pharmacie'   => ['label' => 'Pharmacie',         'icon' => 'capsule',       'href' => route('pharmacie.index'),   'roles' => ['pharmacien', 'docteur_general'],                       'active_routes' => ['pharmacie.index']],
            ],
        ],

        // ── Zone 2 : Hospitalisation (parent pliable) ─────────────────────────
        [
            'type'     => 'section',
            'label'    => 'Hospitalisation',
            'key'      => 'hospitalisation',
            'icon'     => 'building',
            'href'     => route('hospitalisation.index'),
            'roles'    => ['medecin', 'pediatre', 'docteur_general', 'aide_soignante'],
            'active_routes' => ['hospitalisation.index', 'admission-requests.index', 'observation.index', 'chambres.index'],
            'children' => [
                ['label' => 'Hospitalisation',     'icon' => 'building',           'href' => route('hospitalisation.index'),    'route' => 'hospitalisation.index',    'roles' => ['medecin', 'pediatre', 'docteur_general']],
                ['label' => "Demandes d'admission", 'icon' => 'clipboard-document', 'href' => route('admission-requests.index'), 'route' => 'admission-requests.index', 'roles' => ['aide_soignante', 'medecin', 'pediatre', 'docteur_general']],
                ['label' => 'Mise en Observation',  'icon' => 'eye',                'href' => route('observation.index'),         'route' => 'observation.index',         'roles' => ['medecin', 'pediatre', 'docteur_general']],
                ['label' => 'Chambres',             'icon' => 'bed',                'href' => route('chambres.index'),            'route' => 'chambres.index',            'roles' => ['medecin', 'pediatre', 'docteur_general']],
            ],
        ],

        // ── Zone 3 : Administratif ────────────────────────────────────────────
        [
            'type' => 'section',
            'label' => 'Administratif',
            'items' => [
                'caisse'      => ['label' => 'Caisse',         'icon' => 'credit-card',  'href' => route('caisse.index'),      'roles' => ['caissier', 'docteur_general'], 'active_routes' => ['caisse.index', 'caisse.paniers.show']],
                'vacations'   => ['label' => 'Vacations',      'icon' => 'clock',        'href' => route('caisse.sessions.index'), 'roles' => ['caissier', 'docteur_general'], 'active_routes' => ['caisse.sessions.index', 'caisse.sessions.show', 'caisse.historique', 'caisse.jour']],
                'sortie'      => ['label' => 'Sortie patient',  'icon' => 'check-circle', 'href' => route('sortie.index'),      'roles' => ['accueil', 'docteur_general'],   'active_routes' => ['sortie.index']],
                'logistique'  => ['label' => 'Logistique',     'icon' => 'archive-box',  'href' => route('logistique.index'),  'roles' => ['logistique', 'docteur_general'], 'active_routes' => ['logistique.index']],
            ],
        ],

        // ── Zone 4 : Gestion (parent pliable) ─────────────────────────────────
        [
            'type'     => 'section',
            'label'    => 'Gestion',
            'key'      => 'gestion',
            'icon'     => 'briefcase',
            'href'     => route('patients.index'),
            'roles'    => ['accueil', 'docteur_general', 'aide_soignante', 'admin'],
            'active_routes' => [
                'patients.index', 'insurance.index',
                'users.index', 'personnel.index', 'acts.index', 'acts.store', 'acts.update', 'acts.destroy',
            ],
            'children' => [
                ['label' => 'Compagnies d\'assurance', 'icon' => 'shield-check', 'href' => route('insurance.index'),   'route' => 'insurance.index',   'roles' => ['admin', 'docteur_general']],
                ['label' => 'Liste patients',           'icon' => 'users',        'href' => route('patients.index'),    'route' => 'patients.index',    'roles' => ['accueil', 'docteur_general', 'aide_soignante']],
                ['label' => 'Gestion utilisateur',      'icon' => 'user-group',   'href' => route('users.index'),       'route' => 'users.index',       'roles' => ['admin', 'docteur_general']],
                ['label' => 'Actes médicaux',           'icon' => 'document',     'href' => route('acts.index'),        'route' => 'acts.index',        'roles' => ['admin', 'docteur_general']],
            ],
        ],
    ])
    // Le filtrage se fait ici, une seule fois : la vue ne fait plus que rendre.
    // Une section dont il ne reste aucun lien visible est retirée, sinon elle
    // afficherait un titre et une séparation au-dessus d'une liste vide.
    ->map(function ($section) use ($currentUser, $badgeCounts) {
        if (($section['type'] ?? null) !== 'section') {
            return $section;
        }

        $allowed = fn (array $roles): bool => $currentUser
            && $currentUser->role
            && ($currentUser->isAdmin() || in_array($currentUser->role->value, $roles, true));

        if (isset($section['items'])) {
            $section['items'] = collect($section['items'])
                ->filter(fn ($item) => $allowed($item['roles'] ?? []))
                ->map(fn ($item, $key) => array_merge($item, ['badge' => $badgeCounts[$key] ?? 0]))
                ->all();
        } elseif (isset($section['children'])) {
            $section['children'] = collect($section['children'])
                ->filter(fn ($child) => $allowed($child['roles'] ?? $section['roles'] ?? []))
                ->values()
                ->all();
        }

        // Le badge se porte sur la clé de la section parente ; une section
        // plate n'en a pas et n'affiche rien.
        $section['badge'] = isset($section['key']) ? ($badgeCounts[$section['key']] ?? 0) : 0;
        $section['visible'] = count($section['items'] ?? $section['children'] ?? []) > 0;

        return $section;
    })
    ->filter(fn ($section) => $section['visible'] ?? false)
    ->values();
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Clinique Saint Matthieu</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    {{-- Intercept Alpine.start() so inline Blade x-data scripts (attenteBoard, etc.)
         are defined before Alpine scans the DOM. --}}
    <script>
        if (!window.Alpine || !Alpine._deferredStart) {
            (function () {
                var timer = setInterval(function () {
                    if (window.Alpine && typeof Alpine.start === 'function' && !Alpine._deferredStart) {
                        clearInterval(timer);
                        Alpine._deferredStart = Alpine.start;
                        Alpine.start = function () {};
                    }
                }, 10);
            })();
        }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.addEventListener('load', function () {
            if (window.Alpine && Alpine._deferredStart && !Alpine._started) {
                Alpine._started = true;
                Alpine._deferredStart();
            }
        });
    </script>
</head>
<body class="font-sans text-ink antialiased bg-surface-sunken">
    <div class="flex min-h-screen" x-data="{
        mobileNavOpen: false,
        badges: {{ json_encode($badgeCounts) }},
        init() {
            setInterval(() => {
                fetch('{{ route('api.badges') }}')
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(data => { Object.assign(this.badges, data); })
                    .catch(() => {});
            }, 30000);
        }
    }">
        <aside class="hidden w-64 shrink-0 flex-col border-r border-line bg-white lg:flex">
            <x-layout.sidebar-nav :nav="$nav" :active="$active" :badgeCounts="$badgeCounts" />
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
                <x-layout.sidebar-nav :nav="$nav" :active="$active" :badgeCounts="$badgeCounts" />
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

                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-danger/20 bg-danger-tint px-4 py-3 text-sm text-danger">
                            <div class="flex items-center gap-2 font-medium">
                                <x-ui.icon name="alert-triangle" class="h-4 w-4 shrink-0" />
                                <span>Le formulaire n'a pas pu être enregistré.</span>
                            </div>
                            <ul class="mt-2 list-disc space-y-0.5 pl-6">
                                @foreach ($errors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
