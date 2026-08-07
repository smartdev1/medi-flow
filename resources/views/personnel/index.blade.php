<x-layout.app-shell title="Personnel médical" active="personnel" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Personnel médical</h1>
            <p class="mt-1 text-sm text-ink-muted">Gérez les comptes des médecins et pédiatres, et leur planning de garde.</p>
        </div>

        <x-ui.slide-over title="Nouveau médecin">
            <x-slot:trigger>
                <x-ui.button>
                    <x-ui.icon name="user-plus" class="h-4 w-4" />
                    Nouveau médecin
                </x-ui.button>
            </x-slot:trigger>

            <form method="POST" action="{{ route('personnel.store') }}" class="flex flex-col gap-4">
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
                    <x-ui.select label="Spécialité" name="role" placeholder="Sélectionner" :options="['medecin' => 'Médecin généraliste', 'pediatre' => 'Pédiatre']" required />
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>

                <p class="text-xs text-ink-muted">Un mot de passe temporaire sera généré et affiché après la création du compte.</p>

                <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                    <x-ui.button type="submit" class="w-full justify-center">Créer le compte</x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Médecins &amp; pédiatres" value="{{ $doctors->count() }}" icon="stethoscope" trend="Comptes actifs et inactifs" trend-tone="neutral" />
        <x-ui.stat-card label="De garde aujourd'hui" value="{{ $onCallToday->count() }}" icon="alert-triangle" trend="{{ $onCallToday->pluck('name')->join(', ') ?: 'Personne' }}" trend-tone="{{ $onCallToday->count() > 0 ? 'danger' : 'neutral' }}" />
        <x-ui.stat-card label="Gardes programmées" value="{{ $upcomingShifts->flatten()->count() }}" icon="calendar" trend="À partir d'aujourd'hui" trend-tone="neutral" />
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Médecin</th>
                        <th class="px-5 py-3 font-semibold">Spécialité</th>
                        <th class="px-5 py-3 font-semibold">E-mail</th>
                        <th class="px-5 py-3 font-semibold">Garde aujourd'hui</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($doctors as $doctor)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :initials="$doctor->initials" size="sm" />
                                    <p class="font-medium text-ink">{{ $doctor->name }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $doctor->role->label() }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $doctor->email }}</td>
                            <td class="px-5 py-3.5">
                                @if ($doctor->on_call_today > 0)
                                    <x-ui.badge tone="danger">De garde</x-ui.badge>
                                @else
                                    <span class="text-xs text-ink-muted">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$doctor->is_active ? 'success' : 'neutral'">{{ $doctor->is_active ? 'Actif' : 'Désactivé' }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <form method="POST" action="{{ route('personnel.toggle', $doctor) }}">
                                    @csrf
                                    <x-ui.button variant="secondary" size="sm" type="submit">
                                        {{ $doctor->is_active ? 'Désactiver' : 'Réactiver' }}
                                    </x-ui.button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-ui.card>
            <h3 class="text-sm font-semibold text-ink">Programmer une garde</h3>
            <form method="POST" action="{{ route('on-call.store') }}" class="mt-4 flex flex-col gap-4">
                @csrf

                <div>
                    <x-ui.select label="Médecin" name="user_id" placeholder="Sélectionner un médecin" :options="$doctors->pluck('name', 'id')" required />
                    <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-ui.field label="Du" name="start_date" type="date" :value="old('start_date', now()->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                    </div>
                    <div>
                        <x-ui.field label="Au" name="end_date" type="date" :value="old('end_date', now()->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end border-t border-line pt-4">
                    <x-ui.button type="submit">Programmer</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card :padded="false" class="overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h3 class="text-sm font-semibold text-ink">Planning des gardes à venir</h3>
            </div>
            <div class="max-h-96 divide-y divide-line overflow-y-auto">
                @forelse ($upcomingShifts as $date => $shifts)
                    <div class="px-5 py-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">
                            {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l j F') }}
                        </p>
                        <div class="mt-2 flex flex-col gap-2">
                            @foreach ($shifts as $shift)
                                <div class="flex items-center justify-between rounded-md border border-line bg-surface-sunken px-3 py-2">
                                    <span class="text-sm text-ink">{{ $shift->user->name }} <span class="text-xs text-ink-muted">({{ $shift->user->role->label() }})</span></span>
                                    <form method="POST" action="{{ route('on-call.destroy', $shift) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-ink-muted hover:text-danger">
                                            <x-ui.icon name="x-circle" class="h-4 w-4" />
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-6 text-center text-sm text-ink-muted">Aucune garde programmée.</p>
                @endforelse
            </div>
        </x-ui.card>
    </div>

</x-layout.app-shell>
