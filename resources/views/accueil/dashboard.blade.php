<x-layout.app-shell title="Accueil & Réception" active="accueil" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials"
    x-data="{ filter: 'Tout' }"
    @filter-changed="filter = $event.detail.filter">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Accueil & Réception</h1>
            <p class="mt-1 text-sm text-ink-muted">Gestion des arrivées des patients — {{ $today }}.</p>
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
                x-data="patientForm"
                data-old="{{ json_encode(old()) }}"
                @patient-selected="handlePatientSelected($event.detail)"
                @patient-search-cleared="handlePatientSearchCleared()"
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
                        <x-ui.field label="Prénom" name="first_name" x-model="patient.first_name" x-bind:disabled="existingPatientId !== null" required autofocus />
                        <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                    </div>
                    <div>
                        <x-ui.field label="Nom" name="last_name" x-model="patient.last_name" x-bind:disabled="existingPatientId !== null" required />
                        <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-ui.select label="Sexe" name="sex" placeholder="Sélectionner" :options="['M' => 'Masculin', 'F' => 'Féminin']" x-model="patient.sex" x-bind:disabled="existingPatientId !== null" required />
                    <x-input-error :messages="$errors->get('sex')" class="mt-1" />
                </div>

                <div>
                    <x-ui.field label="Date de naissance" name="birth_date" type="date" x-model="patient.birth_date" x-bind:disabled="existingPatientId !== null" />
                    <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
                </div>

                <div>
                    <x-ui.field label="Téléphone" name="phone" icon="phone" placeholder="+225 07 00 00 00 00" x-model="patient.phone" x-bind:disabled="existingPatientId !== null" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>

                <div>
                    <x-ui.select label="Prise en charge" name="coverage" placeholder="Sélectionner" :options="$coverageOptions" x-model="patient.coverage" required />
                    <x-input-error :messages="$errors->get('coverage')" class="mt-1" />
                </div>

                <div x-show="patient.coverage === 'Assuré'">
                    <x-ui.select label="Compagnie d'assurance" name="insurance_company_id" placeholder="Sélectionner" :options="$insuranceCompanies->pluck('name', 'id')" />
                    <x-input-error :messages="$errors->get('insurance_company_id')" class="mt-1" />
                </div>

                <div x-show="patient.coverage === 'Assuré'">
                    <x-ui.field label="Ticket modérateur (%)" name="ticket_moderateur" type="number" x-model="patient.ticket_moderateur" placeholder="20" :value="old('ticket_moderateur', config('clinic.default_ticket_rate', 20))" min="0" max="100" step="0.01" />
                    <x-input-error :messages="$errors->get('ticket_moderateur')" class="mt-1" />
                    <p class="mt-1 text-xs text-ink-muted">Part payée par le patient (ex: 20 = 20%)</p>
                </div>

                <div>
                    <label class="text-sm font-medium text-ink">Acte demandé</label>
                    <select name="act_id" x-ref="actSelect" x-model="patient.act_id" @change="updateRouting()" class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">— Sélectionner l'acte —</option>
                        @foreach ($acts->groupBy('category') as $category => $categoryActs)
                            <optgroup label="{{ \App\Enums\ActCategory::from($category)->label() }}">
                                @foreach ($categoryActs as $act)
                                    <option value="{{ $act->id }}" data-code="{{ $act->code }}">{{ $act->label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div x-show="routingDestination" x-cloak class="mt-2 flex items-center gap-2 rounded-md bg-brand-50 px-3 py-2">
                        <x-ui.icon name="navigation" class="h-4 w-4 text-brand" />
                        <span class="text-sm text-ink-soft">Orientation suggérée :</span>
                        <span class="text-sm font-medium text-brand" x-text="routingDestination"></span>
                    </div>
                    <p class="mt-1 text-xs text-ink-muted">Permet à la caisse de pré-remplir la facture automatiquement.</p>
                    <x-input-error :messages="$errors->get('act_id')" class="mt-1" />
                </div>

                <div x-show="isSurgeryCategory" x-cloak>
                    <x-ui.select label="Type de chirurgie" name="surgery_type" placeholder="Sélectionner" :options="['interne' => 'Interne (chirurgien de la clinique)', 'externe' => 'Externe (chirurgien extérieur)']" x-model="patient.surgery_type" />
                    <x-input-error :messages="$errors->get('surgery_type')" class="mt-1" />
                    <p class="mt-1 text-xs text-ink-muted">Externe = location de bloc opératoire facturée en supplément.</p>
                </div>

                <div x-show="patient.surgery_type === 'externe'" x-cloak>
                    <x-ui.field label="Nom du chirurgien externe" name="referring_doctor_name" placeholder="Dr. Nom Prénom" x-model="patient.referring_doctor_name" />
                    <x-input-error :messages="$errors->get('referring_doctor_name')" class="mt-1" />
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

    @if (count($observationPatients) > 0)
        <div class="mt-6 rounded-md border border-warning/30 bg-warning/5 p-4">
            <div class="flex items-center justify-between gap-3 mb-3">
                <div class="flex items-center gap-2">
                    <x-ui.icon name="eye" class="h-5 w-5 text-warning" />
                    <h3 class="text-sm font-semibold text-warning">Patients en observation : {{ count($observationPatients) }}</h3>
                </div>
            </div>
            <div class="divide-y divide-line/50">
                @foreach ($observationPatients as $patient)
                    <div class="flex items-center justify-between py-2.5 text-sm">
                        <div class="flex items-center gap-3">
                            <x-ui.avatar :initials="$patient['initials']" size="sm" />
                            <div>
                                <p class="font-medium text-ink">{{ $patient['name'] }}</p>
                                <p class="font-mono text-xs text-ink-muted">{{ $patient['code'] }}</p>
                            </div>
                            <span class="text-xs text-ink-muted">
                                {{ $patient['room'] }} — {{ $patient['ward'] }}
                            </span>
                        </div>
                        <x-ui.badge :tone="$patient['status_tone']" class="text-xs">
                            {{ $patient['reason'] }}
                        </x-ui.badge>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

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
                        <th class="px-5 py-3 font-semibold">Prise en charge</th>
                        <th class="px-5 py-3 font-semibold">Statut</th>
                        <th class="px-5 py-3 font-semibold text-right">Jours</th>
                        <th class="px-5 py-3 font-semibold text-right">Montant en cours</th>
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($patients as $patient)
                        <tr class="hover:bg-surface-sunken">
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
                            <td class="px-5 py-3.5">
                                <x-ui.badge tone="neutral">{{ $patient['coverage'] }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$patient['status_tone']">{{ $patient['status'] }}</x-ui.badge>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-ink-soft">
                                {{ $patient['stay_days'] ?? '—' }}
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($patient['can_edit_stay_amount'])
                                    <form id="stay-amount-{{ $patient['visit_id'] }}" method="POST" action="{{ route('accueil.visits.stay-amount', $patient['visit_id']) }}">
                                        @csrf
                                        <input type="text"
                                               name="stay_amount"
                                               inputmode="numeric"
                                               value="{{ old('stay_amount', $patient['agreed_amount']) }}"
                                               placeholder="{{ number_format($patient['automatic_amount'], 0, ',', ' ') }}"
                                               class="w-full rounded-md border border-line bg-surface-sunken px-2 py-1.5 text-right font-mono text-sm text-ink focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500">
                                    </form>
                                    @if ($patient['agreed_amount'] === null)
                                        <p class="mt-1 text-xs text-ink-muted">calculé sur {{ $patient['stay_days'] }} jour(s)</p>
                                    @endif
                                @elseif ($patient['stay_amount'] !== null)
                                    <span class="font-mono text-ink">{{ number_format($patient['stay_amount'], 0, ',', ' ') }} FCFA</span>
                                    @if ($patient['agreed_amount'] !== null)
                                        <p class="mt-1 text-xs text-ink-muted">montant convenu</p>
                                    @else
                                        <p class="mt-1 text-xs text-ink-muted">sur {{ $patient['stay_days'] }} jour(s)</p>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($patient['can_edit_stay_amount'])
                                    <x-ui.button type="submit" form="stay-amount-{{ $patient['visit_id'] }}" size="sm">Confirmer</x-ui.button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-between border-t border-line px-5 py-3 text-sm text-ink-muted">
            <p>{{ count($patients) }} arrivée(s) affichée(s) / {{ count($patients) }} arrivée(s) enregistrée(s) aujourd'hui</p>
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

<script>
    function patientForm() {
        const oldData = @json(old());
        const selectedPatient = oldData && oldData.existing_patient_id ? null : null;

        return {
            existingPatientId: selectedPatient ? selectedPatient.id : null,
            patient: {
                first_name: (oldData && oldData.first_name) || '',
                last_name: (oldData && oldData.last_name) || '',
                sex: (oldData && oldData.sex) || '',
                birth_date: (oldData && oldData.birth_date) || '',
                phone: (oldData && oldData.phone) || '',
                coverage: (oldData && oldData.coverage) || '',
                insurance_company_id: (oldData && oldData.insurance_company_id) || '',
                ticket_moderateur: (oldData && oldData.ticket_moderateur) || '',
                act_id: (oldData && oldData.act_id) || '',
                surgery_type: (oldData && oldData.surgery_type) || '',
                referring_doctor_name: (oldData && oldData.referring_doctor_name) || '',
            },
            routingDestination: '',
            isSurgeryCategory: false,

            handlePatientSelected(option) {
                this.existingPatientId = option.id;
                this.patient = {
                    first_name: option.first_name || '',
                    last_name: option.last_name || '',
                    sex: option.sex || '',
                    birth_date: option.birth_date || '',
                    phone: option.phone || '',
                    coverage: option.coverage || '',
                    insurance_company_id: '',
                    ticket_moderateur: '',
                    act_id: '',
                    surgery_type: '',
                    referring_doctor_name: '',
                };
                this.routingDestination = '';
                this.isSurgeryCategory = false;
            },

            handlePatientSearchCleared() {
                this.existingPatientId = null;
            },

            updateRouting() {
                const select = this.$refs.actSelect || this.$el.querySelector('select[name="act_id"]');
                if (!select) { this.routingDestination = ''; return; }
                const selectedOption = select.options[select.selectedIndex];
                if (!selectedOption || !selectedOption.dataset.code) { this.routingDestination = ''; return; }

                const code = selectedOption.dataset.code;
                this.isSurgeryCategory = code.startsWith('CHIR-') || code.startsWith('BLOC-');
                if (!this.isSurgeryCategory) {
                    this.patient.surgery_type = '';
                    this.patient.referring_doctor_name = '';
                }
                const firstLetter = code.charAt(0).toUpperCase();
                const destinations = {
                    'C': 'Caisse',
                    'L': 'Laboratoire',
                    'P': 'Pharmacie',
                    'M': 'Médecin',
                    'H': 'Hospitalisation',
                    'S': 'Sortie',
                };
                this.routingDestination = destinations[firstLetter] || 'Orientation inconnue';
            }
        };
    }
</script>



