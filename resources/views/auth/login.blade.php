<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Connexion — Clinique Saint Matthieu</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-ink antialiased">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Panneau de marque --}}
        <div class="relative hidden overflow-hidden bg-brand-900 px-12 py-12 lg:flex lg:flex-col lg:justify-between">
            <div class="pointer-events-none absolute inset-0 opacity-[0.06]" aria-hidden="true">
                <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full border-[40px] border-white"></div>
                <div class="absolute -bottom-32 -right-16 h-[28rem] w-[28rem] rounded-full border-[40px] border-white"></div>
            </div>

            <div class="relative flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-white/10 text-white">
                    <x-ui.icon name="heart" class="h-5 w-5" />
                </span>
                <span class="font-semibold text-white">Clinique Saint Matthieu</span>
            </div>

            <div class="relative max-w-md">
                <h1 class="text-3xl font-semibold leading-tight text-white">
                    Le parcours patient, coordonné du premier contact à la sortie.
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-brand-100">
                    Accueil, caisse, consultation, laboratoire, pharmacie&nbsp;: une seule plateforme pour suivre chaque patient, en temps réel, à chaque étape de son passage en clinique.
                </p>

                <ul class="mt-8 space-y-3">
                    @foreach ([
                        'Suivi du statut patient en temps réel, service par service',
                        'Dossier, historique et documents centralisés',
                        'File d\'attente et orientation automatisées',
                    ] as $benefit)
                        <li class="flex items-start gap-2.5 text-sm text-brand-50">
                            <x-ui.icon name="check-circle" class="mt-0.5 h-4 w-4 shrink-0 text-brand-300" />
                            <span>{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="relative flex items-center gap-2 text-xs text-brand-200">
                <x-ui.icon name="shield-check" class="h-4 w-4" />
                <span>Hébergement de données de santé certifié — conforme RGPD</span>
            </div>
        </div>

        {{-- Panneau de connexion --}}
        <div class="flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-20">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-brand-500 text-white">
                        <x-ui.icon name="heart" class="h-5 w-5" />
                    </span>
                    <span class="font-semibold text-ink">Clinique Saint Matthieu</span>
                </div>

                <p class="text-xs font-semibold uppercase tracking-wide text-brand-600">Espace professionnel</p>
                <h2 class="mt-2 text-2xl font-semibold text-ink">Bon retour</h2>
                <p class="mt-1 text-sm text-ink-muted">Connectez-vous avec vos identifiants professionnels.</p>

                <x-auth-session-status class="mt-6" :status="session('status')" />

                <form class="mt-8 space-y-5" method="POST" action="{{ route('login') }}">
                    @csrf

                    <div>
                        <x-ui.field
                            label="Adresse e-mail"
                            name="email"
                            type="email"
                            icon="mail"
                            placeholder="prenom.nom@clinique-saintmatthieu.ci"
                            :value="old('email')"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <x-ui.field
                            label="Mot de passe"
                            name="password"
                            type="password"
                            icon="lock"
                            placeholder="••••••••••"
                            required
                            autocomplete="current-password"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center gap-2 text-ink-soft">
                            <input type="checkbox" name="remember" class="rounded border-line text-brand-500 focus:ring-brand-500">
                            Se souvenir de moi
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="font-medium text-brand-600 hover:text-brand-700">Mot de passe oublié&nbsp;?</a>
                        @endif
                    </div>

                    <x-ui.button type="submit" class="w-full justify-center">
                        Se connecter
                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                    </x-ui.button>
                </form>

                <p class="mt-8 text-center text-xs text-ink-muted">
                    Accès restreint au personnel autorisé de la Clinique Saint Matthieu.
                    <br>Besoin d'aide&nbsp;? Contactez votre administrateur système.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
