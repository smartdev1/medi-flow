<x-layout.app-shell title="Accueil & Réception" active="accueil" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials"
    x-data="{ filter: 'Tout' }"
    @filter-changed="filter = $event.detail.filter">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Accueil & Réception</h1>
            <p class="mt-1 text-sm text-ink-muted">Gestion des arrivées et de l'orientation des patients — {{ $today }}.</p>
        </div>
        <x-ui.slide-over title="Nouveau patient">
            <x-slot:trigger>
                <x-ui.button>
                    <x-ui.icon name="plus" class="h-4 w-4" />
                    Nouveau patient
                </x-ui.button>
            </x-slot:trigger>

            <form
                method="POST"
                action="{{ route('patients.store') }}"
                class="flex flex-col gap-4"
                x-data="{
                    existingPatientId: null,
                    form: {
                        first_name: @js(old('first_name', '')),
                        last_name: @js(old('last_name', '')),
                        sex: @js(old('sex', '')),
                        birth_date: @js(old('birth_date', '')),
                        phone: @js(old('phone', '')),
                        coverage: @js(old('coverage', '')),
                    },
                }"
                @patient-selected="
                    existingPatientId = $event.detail.id;
                    form.first_name = $event.detail.first_name;
                    form.last_name = $event.detail.last_name;
                    form.sex = $event.detail.sex;
                    form.birth_date = $event.detail.birth_date ?? '';
                    form.phone = $event.detail.phone ?? '';
                    form.coverage = $event.detail.coverage;
                "
                @patient-search-cleared="existingPatientId = null"
            >
                @csrf

                <div>
                    <x-ui.patient-search
                        name="existing_patient_id"
                        label="Patient déjà existant ?"
                        placeholder="Rechercher par CSM-XXXX ou par nom..."
                        :options="$patientOptions"
                    />
                    <p class="mt-1 text-xs text-ink-muted">Laissez vide pour créer un nouveau dossier patient.</p>
                    <p x-show="existingPatientId" x-cloak class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-success">
                        <x-ui.icon name="check-circle" class="h-3.5 w-3.5" />
                        Dossier existant chargé — les champs ci-dessous ont été pré-remplis.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <x-ui.field label="Prénom" name="first_name" x-model="form.first_name" x-bind:disabled="existingPatientId !== null" required autofocus />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-ui.field label="Nom" name="last_name" x-model="form.last_name" x-bind:disabled="existingPatientId !== null" required />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-ui.select label="Sexe" name="sex" placeholder="Sélectionner" :options="['M' => 'Masculin', 'F' => 'Féminin']" x-model="form.sex" x-bind:disabled="existingPatientId !== null" required />
                    <x-input-error :messages="$errors->get('sex')" class="mt-1" />
                </div>

                <div>
                    <x-ui.field label="Date de naissance" name="birth_date" type="date" x-model="form.birth_date" x-bind:disabled="existingPatientId !== null" />
                    <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                </div>

                <div>
                    <x-ui.field label="Téléphone" name="phone" icon="phone" placeholder="+225 07 00 00 00 00" x-model="form.phone" x-bind:disabled="existingPatientId !== null" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>

                <div>
                    <x-ui.select label="Prise en charge" name="coverage" placeholder="Sélectionner" :options="$coverageOptions" x-model="form.coverage" x-bind:disabled="existingPatientId !== null" required />
                    <x-input-error :messages="$errors->get('coverage')" class="mt-1" />
                </div>

                <div x-show="form.coverage === 'Assurance'">
                    <x-ui.select label="Compagnie d’assurance" name="insurance_company_id" placeholder="Sélectionner" :options="$insuranceCompanies->pluck('name', 'id')" />
                    <x-input-error :messages="$errors->get('insurance_company_id')" class="mt-1" />
                </div>

                <div>
                    <x-ui.select label="Orientation" name="orientation" placeholder="Sélectionner un service" :options="$orientationOptions" required />
                    <x-input-error :messages="$errors->get('orientation')" class="mt-1" />
                </div>

                <div>
                    <x-ui.field label="Motif de la visite" name="reason" placeholder="Ex : Fièvre, contrôle de routine..." :value="old('reason')" />
                    <p class="mt-1 text-xs text-ink-muted">En langage général — le diagnostic reste au médecin.</p>
                    <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                </div>

                <label class="flex items-center gap-2 text-sm text-ink-soft">
                    <input type="checkbox" name="urgent" value="1" class="rounded border-line text-danger focus:ring-danger">
                    Cas prioritaire / urgent
                </label>

                <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                    <x-ui.button type="submit" class="w-full justify-center">Enregistrer le patient</x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Arrivées aujourd'hui" value="{{ $arrivalsToday }}" icon="user-plus" trend="Visites du jour" trend-tone="success" />
        <x-ui.stat-card label="En attente" value="{{ $waitingCount }}" icon="clock" trend="Avant consultation" trend-tone="neutral" />
        <x-ui.stat-card label="Urgences" value="{{ $urgentCount }}" icon="alert-triangle" trend="Priorité immédiate" trend-tone="danger" />
        <x-ui.stat-card label="Taux d'occupation" value="{{ $occupancyRate }}%" icon="chart-bar" trend="Capacité clinique" trend-tone="neutral" />
    </div>

    <x-ui.filter-pills class="mt-6" :items="$filters" label="Filtrer par prise en charge">
        <x-slot:trailing>
            <x-ui.icon name="calendar" class="h-4 w-4" />
            {{ $today }}
        </x-slot:trailing>
    </x-ui.filter-pills>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Patient</th>
                        <th class="px-5 py-3 font-semibold">Arrivée</th>
                        <th class="px-5 py-3 font-semibold">Orientation</th>
                        <th class="px-5 py-3 font-semibold">Prise en charge</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($patients as $patient)
                        <tr class="hover:bg-surface-sunken"
                            x-show="filter === 'Tout' || patient.coverage === filter"
                            x-cloak
                            x-data="{ patient: @js($patient) }">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :initials="$patient['initials']" :tone="$patient['urgent'] ? 'danger' : 'brand'" size="sm" />
                                    <div>
                                        <p class="font-medium text-ink">{{ $patient['name'] }}</p>
                                        <p class="font-mono text-xs text-ink-muted">{{ $patient['id'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-ink-soft">{{ $patient['arrival'] }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $patient['orientation'] }}</td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge tone="neutral">{{ $patient['coverage'] }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$patient['status_tone']">{{ $patient['status'] }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <x-ui.button variant="secondary" size="sm">Détails</x-ui.button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-line px-5 py-3 text-sm text-ink-muted">
            <p><span x-text="$el.parentElement.querySelectorAll('tbody tr[x-show]:not([style*=\"display: none\"])').length"></span> arrivée(s) affichée(s) / {{ count($patients) }} arrivée(s) enregistrée(s) aujourd'hui</p>
            <div class="flex gap-1">
                <button class="flex h-8 w-8 items-center justify-center rounded-md border border-line text-ink-muted hover:bg-surface-sunken" disabled>
                    <x-ui.icon name="chevron-left" class="h-4 w-4" />
                </button>
                <button class="flex h-8 w-8 items-center justify-center rounded-md border border-line text-ink-muted hover:bg-surface-sunken">
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </x-ui.card>

</x-layout.app-shell>



