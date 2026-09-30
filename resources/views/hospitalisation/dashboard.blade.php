<x-layout.app-shell title="Hospitalisation" active="hospitalisation" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Hospitalisations en cours</h1>
            <p class="mt-1 text-sm text-ink-muted">Suivi des patients admis et de leur prise en charge.</p>
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="secondary" :href="route('chambres.index')">
                <x-ui.icon name="bed" class="h-4 w-4" />
                Carte des chambres
            </x-ui.button>

            <x-ui.slide-over title="Nouvelle admission">
                <x-slot:trigger>
                    <x-ui.button>
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        Nouvelle admission
                    </x-ui.button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('admissions.store') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <x-ui.patient-search :options="$patientOptions" />
                        <x-input-error :messages="$errors->get('patient_id')" class="mt-1" />
                    </div>

                    <div>
                        <label class="text-sm font-medium text-ink">Unité de prise en charge</label>
                        <select name="type" class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="hospitalisation">Hospitalisation</option>
                            <option value="observation">Mise en observation</option>
                        </select>
                        <p class="mt-1 text-xs text-ink-muted">Détermine l'unité où le patient sera suivi.</p>
                        <x-input-error :messages="$errors->get('type')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.select label="Chambre" name="room_id" placeholder="Sélectionner une chambre disponible" :options="$roomOptions" required />
                        <x-input-error :messages="$errors->get('room_id')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.field label="Motif d'admission" name="reason" placeholder="Ex : Surveillance post-opératoire" :value="old('reason')" required />
                        <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                    </div>

                    <div>
                        <x-ui.select label="Médecin / service référent" name="attending" placeholder="Sélectionner le médecin traitant" :options="$onCallDoctorOptions" :value="old('attending')" />
                        <x-input-error :messages="$errors->get('attending')" class="mt-1" />
                    </div>

                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                        <x-ui.button type="submit" class="w-full justify-center">Admettre le patient</x-ui.button>
                    </div>
                </form>
            </x-ui.slide-over>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-ui.stat-card label="Patients hospitalisés" value="{{ count($patients) }}" icon="building" trend="Toutes unités" trend-tone="neutral" />
        <x-ui.stat-card label="Sorties prévues" value="{{ $plannedDischarges }}" icon="check-circle" trend="À préparer" trend-tone="warning" />
        <x-ui.stat-card label="Durée moyenne de séjour" value="{{ $averageStay }}" icon="clock" trend="Sur les 7 derniers jours" trend-tone="neutral" />
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Patient</th>
                        <th class="px-5 py-3 font-semibold">Chambre</th>
                        <th class="px-5 py-3 font-semibold">Service</th>
                        <th class="px-5 py-3 font-semibold">Motif</th>
                        <th class="px-5 py-3 font-semibold">Admission</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($patients as $patient)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :initials="$patient['initials']" size="sm" />
                                    <div>
                                        <p class="font-medium text-ink">{{ $patient['name'] }}</p>
                                        <p class="font-mono text-xs text-ink-muted">{{ $patient['code'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-ink-soft">{{ $patient['room'] }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $patient['ward'] }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $patient['reason'] }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $patient['admitted'] }}</td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$patient['status_tone']">{{ $patient['status'] }}</x-ui.badge>
                            </td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.button variant="secondary" size="sm" :href="route('patients.show', $patient['patient_id'])">Voir dossier</x-ui.button>

                                        @if ($patient['discharge_planned'])
                                            <form method="POST" action="{{ route('admissions.discharge', $patient['id']) }}">
                                                @csrf
                                                <x-ui.button size="sm" type="submit">Enregistrer la sortie</x-ui.button>
                                            </form>
                                            <form method="POST" action="{{ route('admissions.cancel-discharge', $patient['id']) }}">
                                                @csrf
                                                <x-ui.button variant="secondary" size="sm" type="submit">Annuler</x-ui.button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admissions.plan-discharge', $patient['id']) }}">
                                                @csrf
                                                <x-ui.button size="sm" type="submit">Programmer la sortie</x-ui.button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

</x-layout.app-shell>
