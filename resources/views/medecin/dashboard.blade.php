<x-layout.app-shell title="Espace Médecin" active="medecin" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div x-data="medecinDashboard()" x-init="startPolling()" class="space-y-6">

        {{-- En-tête --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink">Espace Médecin</h1>
                <p class="mt-1 text-sm text-ink-muted" x-text="queue.length + ' patient(s) en attente de consultation aujourd\'hui.'"></p>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.badge tone="brand">
                    <span x-text="lastRefresh ? 'Dernière MAJ : ' + lastRefresh : 'Chargement...'"></span>
                </x-ui.badge>
                <button type="button" @click="refresh()"
                    class="flex items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-sm text-ink-soft hover:bg-surface-muted"
                    :disabled="loading">
                    <template x-if="loading">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 animate-spin">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 9 9" />
                        </svg>
                    </template>
                    <template x-if="!loading">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-2.636-6.364M21 3v6h-6" />
                        </svg>
                    </template>
                    <span>Rafraîchir</span>
                </button>
                @if ($isOnCallToday)
                    <x-ui.badge tone="danger">
                        <x-ui.icon name="alert-triangle" class="h-3.5 w-3.5" />
                        De garde aujourd'hui
                    </x-ui.badge>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- File d'attente (Alpine réactive) --}}
            <x-ui.card :padded="false" class="lg:col-span-1">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="text-sm font-semibold text-ink">File d'attente</h2>
                    <span class="text-xs text-ink-muted" x-text="queue.length + ' patients'"></span>
                </div>
                <template x-if="queue.length === 0">
                    <div class="px-5 py-8 text-center text-sm text-ink-muted">Aucun patient dans cette file.</div>
                </template>
                <template x-for="item in queue" :key="item.href">
                    <button type="button"
                            @click="confirmPatient(item.name, item.href)"
                            class="flex w-full items-start gap-3 px-5 py-4 text-left hover:bg-surface-sunken transition-colors">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                              :class="(item.urgent ?? false) ? 'bg-danger-tint text-danger' : 'bg-brand-50 text-brand-700'"
                              x-text="item.initials"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-medium text-ink" x-text="item.name"></p>
                                <span class="font-mono text-xs text-ink-muted" x-text="item.time"></span>
                            </div>
                            <p class="mt-0.5 truncate text-xs text-ink-muted" x-text="item.meta"></p>
                            <div class="mt-2" x-show="item.urgent">
                                <span class="inline-flex items-center rounded-md border border-danger/20 bg-danger-tint px-2 py-1 text-xs font-medium text-danger">Urgent</span>
                            </div>
                        </div>
                    </button>
                </template>
            </x-ui.card>

            {{-- Fiche patient (server-rendered, navigation au clic) --}}
            <div class="flex flex-col gap-6 lg:col-span-2">
                @if (!$patient)
                    <x-ui.card class="flex flex-col items-center gap-2 py-12 text-center">
                        <x-ui.icon name="check-circle" class="h-8 w-8 text-success" />
                        <p class="font-medium text-ink">Aucun patient sélectionné</p>
                        <p class="text-sm text-ink-muted">Sélectionnez un patient dans la file d'attente pour commencer la consultation.</p>
                    </x-ui.card>
                @else
                    <x-ui.card>
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-start gap-4">
                                <x-ui.avatar :initials="$patient['initials']" size="lg" />
                                <div>
                                    <h2 class="text-lg font-semibold text-ink">{{ $patient['name'] }}</h2>
                                    <p class="font-mono text-xs text-ink-muted">{{ $patient['id'] }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-ui.badge tone="neutral">{{ $patient['age'] ?? '—' }} ans · {{ $patient['sex'] }}</x-ui.badge>
                                        <x-ui.badge tone="warning">Allergie&nbsp;: {{ $patient['allergy'] }}</x-ui.badge>
                                        <x-ui.badge tone="neutral">{{ $patient['coverage'] }}</x-ui.badge>
                                    </div>
                                    @if (!empty($patient['guardian_name']))
                                        <p class="mt-2 text-xs text-ink-muted">
                                            Parent/tuteur&nbsp;: <span class="font-medium text-ink-soft">{{ $patient['guardian_name'] }}</span>
                                            · Contact d'urgence&nbsp;: <span class="font-mono">{{ $patient['emergency_contact'] }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-wrap justify-end gap-2">
                                <x-ui.slide-over title="Demander un examen">
                                    <x-slot:trigger>
                                        <x-ui.button variant="secondary" size="sm">
                                            <x-ui.icon name="beaker" class="h-4 w-4" />
                                            Labo
                                        </x-ui.button>
                                    </x-slot:trigger>
                                    <form method="POST" action="{{ route('exams.store') }}" class="flex flex-col gap-4">
                                        @csrf
                                        <input type="hidden" name="patient_id" value="{{ $patient['record_id'] }}">
                                        <input type="hidden" name="visit_id" value="{{ $patient['visit_id'] }}">
                                        <p class="text-sm text-ink-muted">Patient&nbsp;: <span class="font-medium text-ink">{{ $patient['name'] }}</span> ({{ $patient['id'] }})</p>
                                        <div>
                                            <x-ui.lab-exam-picker />
                                            <x-input-error :messages="$errors->get('items')" class="mt-1" />
                                            <x-input-error :messages="$errors->get('items.*.lab_exam_id')" class="mt-1" />
                                        </div>
                                        <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                            <x-ui.button type="submit" class="w-full justify-center">Envoyer au laboratoire</x-ui.button>
                                        </div>
                                    </form>
                                </x-ui.slide-over>

                                <x-ui.slide-over title="Nouvelle ordonnance">
                                    <x-slot:trigger>
                                        <x-ui.button size="sm">
                                            <x-ui.icon name="document" class="h-4 w-4" />
                                            Ordonnance
                                        </x-ui.button>
                                    </x-slot:trigger>
                                    <form method="POST" action="{{ route('prescriptions.store') }}" class="flex flex-col gap-4">
                                        @csrf
                                        <input type="hidden" name="patient_id" value="{{ $patient['record_id'] }}">
                                        <input type="hidden" name="visit_id" value="{{ $patient['visit_id'] }}">
                                        <p class="text-sm text-ink-muted">Patient&nbsp;: <span class="font-medium text-ink">{{ $patient['name'] }}</span> ({{ $patient['id'] }})</p>
                                        <div>
                                            <x-ui.field label="Date de l'ordonnance" name="date" type="date" :value="old('date', now()->format('Y-m-d'))" required />
                                            <x-input-error :messages="$errors->get('date')" class="mt-1" />
                                        </div>
                                        <div x-data="{ items: @js(old('items', [['medication_id' => '', 'posology' => '', 'quantity' => 1]])) }" class="flex flex-col gap-2">
                                            <label class="text-sm font-medium text-ink">Médicaments</label>
                                            <template x-for="(item, index) in items" :key="index">
                                                <div class="flex flex-col gap-2 rounded-md border border-line p-2">
                                                    <div class="flex items-end gap-2">
                                                        <div class="flex-1">
                                                            <select :name="`items[${index}][medication_id]`" x-model="item.medication_id" class="w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                                                <option value="">Sélectionner un médicament</option>
                                                                @foreach ($medications as $medication)
                                                                    <option value="{{ $medication->id }}">{{ $medication->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="w-20">
                                                            <input type="number" min="1" :name="`items[${index}][quantity]`" x-model="item.quantity" placeholder="Qté" class="w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                                        </div>
                                                        <button type="button" @click="items.length > 1 && items.splice(index, 1)" class="shrink-0 rounded-md p-2 text-ink-muted hover:bg-surface-muted">
                                                            <x-ui.icon name="x-circle" class="h-4 w-4" />
                                                        </button>
                                                    </div>
                                                    <input
                                                        type="text"
                                                        :name="`items[${index}][posology]`"
                                                        x-model="item.posology"
                                                        placeholder="Posologie : 1 comprimé matin et soir pendant 5 jours"
                                                        maxlength="255"
                                                        class="w-full rounded-md border border-line bg-white py-2 px-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                                    >
                                                </div>
                                            </template>
                                            <button type="button" @click="items.push({ medication_id: '', posology: '', quantity: 1 })" class="mt-1 flex items-center gap-1.5 self-start text-sm font-medium text-brand-600 hover:text-brand-700">
                                                <x-ui.icon name="plus" class="h-4 w-4" />
                                                Ajouter un médicament
                                            </button>
                                            <x-input-error :messages="$errors->get('items')" class="mt-1" />
                                            <x-input-error :messages="$errors->get('items.*.medication_id')" class="mt-1" />
                                            <x-input-error :messages="$errors->get('items.*.posology')" class="mt-1" />
                                            <x-input-error :messages="$errors->get('items.*.quantity')" class="mt-1" />
                                        </div>
                                        <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                            <x-ui.button type="submit" class="w-full justify-center">Enregistrer l'ordonnance</x-ui.button>
                                        </div>
                                    </form>
                                </x-ui.slide-over>
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card>
                        <form method="POST" action="{{ route('consultations.update', $consultation) }}" class="flex flex-col gap-4">
                            @csrf
                            <div>
                                <label class="text-sm font-medium text-ink" for="act_id">Acte réalisé</label>
                                <select
                                    name="act_id"
                                    id="act_id"
                                    class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                >
                                    <option value="">— Non renseigné —</option>
                                    @foreach ($acts->groupBy('category') as $category => $categoryActs)
                                        <optgroup label="{{ \App\Enums\ActCategory::from($category)->label() }}">
                                            @foreach ($categoryActs as $actOption)
                                                <option value="{{ $actOption->id }}" @selected(old('act_id', $currentActId) == $actOption->id)>
                                                    {{ $actOption->label }} — {{ number_format($actOption->base_price, 0, ',', ' ') }} FCFA
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-ink-muted">
                                    Seuls les actes réalisés sont facturés. Sans acte, la consultation reste à 0 FCFA.
                                </p>
                                <x-input-error :messages="$errors->get('act_id')" class="mt-1" />
                            </div>
                            <div>
                                <x-ui.textarea label="Diagnostic & notes" name="notes" :rows="4" placeholder="Symptômes, diagnostic, recommandations...">{{ old('notes', $consultation->notes) }}</x-ui.textarea>
                            </div>
                            <div class="mt-2 flex justify-end border-t border-line pt-4">
                                <x-ui.button size="sm" type="submit">Enregistrer la consultation</x-ui.button>
                            </div>
                        </form>

                        <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                            <x-ui.slide-over title="Orienter vers un service">
                                <x-slot:trigger>
                                    <x-ui.button variant="secondary" size="sm" type="button">
                                        <x-ui.icon name="arrow-right" class="h-4 w-4" />
                                        Orienter vers un service
                                    </x-ui.button>
                                </x-slot:trigger>
                                <form method="POST" action="{{ route('referrals.store') }}" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="patient_id" value="{{ $patient['record_id'] }}">
                                    <p class="text-sm text-ink-muted">Patient&nbsp;: <span class="font-medium text-ink">{{ $patient['name'] }}</span> ({{ $patient['id'] }})</p>
                                    <div>
                                        <x-ui.select label="Service" name="service" placeholder="Sélectionner un service" :options="$serviceOptions" required />
                                        <x-input-error :messages="$errors->get('service')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-ui.textarea label="Motif de l'orientation" name="reason" placeholder="Ex : Baisse d'acuité visuelle à évaluer" required></x-ui.textarea>
                                        <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                                    </div>
                                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                        <x-ui.button type="submit" class="w-full justify-center">Confirmer l'orientation</x-ui.button>
                                    </div>
                                </form>
                            </x-ui.slide-over>

                            <form method="POST" action="{{ route('medecin.visits.hospitalize', $patient['visit_id']) }}">
                                @csrf
                                <x-ui.button variant="danger" size="sm" type="submit">
                                    <x-ui.icon name="building" class="h-4 w-4" />
                                    Hospitaliser
                                </x-ui.button>
                            </form>

                            <form method="POST" action="{{ route('medecin.visits.observe', $patient['visit_id']) }}">
                                @csrf
                                <x-ui.button variant="secondary" size="sm" type="submit">
                                    <x-ui.icon name="eye" class="h-4 w-4" />
                                    Mettre en observation
                                </x-ui.button>
                            </form>

                            <form method="POST" action="{{ route('consultations.close', $consultation) }}">
                                @csrf
                                <x-ui.button size="sm" type="submit">Clôturer la consultation</x-ui.button>
                            </form>
                        </div>

                        @if ($patient['referrals']->isNotEmpty())
                            <div class="mt-4 flex flex-col gap-2 border-t border-line pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Orientations en cours</p>
                                @foreach ($patient['referrals'] as $referral)
                                    <div class="flex items-center justify-between rounded-md border border-line bg-surface-sunken px-3 py-2">
                                        <div>
                                            <p class="text-sm font-medium text-ink">{{ $referral->service }}</p>
                                            <p class="text-xs text-ink-muted">{{ $referral->reason }}</p>
                                        </div>
                                        <x-ui.badge :tone="$referral->status_tone">{{ $referral->status }}</x-ui.badge>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-ui.card>

                    @if ($selectedVisit && $selectedVisit->surgery_type)
                        <x-ui.card>
                            <div x-data="{ showSurgeryForm: false }" class="flex flex-col gap-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-semibold text-ink">Programmer une chirurgie</h3>
                                    <button type="button" @click="showSurgeryForm = !showSurgeryForm"
                                        class="flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700">
                                        <x-ui.icon name="plus" class="h-4 w-4" />
                                        <span x-text="showSurgeryForm ? 'Masquer' : 'Nouvelle chirurgie'"></span>
                                    </button>
                                </div>

                                <form x-show="showSurgeryForm" x-cloak method="POST"
                                    action="{{ route('surgery.store', $selectedVisit) }}"
                                    class="flex flex-col gap-3 rounded-md border border-line bg-surface-sunken p-4">
                                    @csrf
                                    <div>
                                        <label class="text-sm font-medium text-ink" for="procedure_name">Nom de l'intervention</label>
                                        <input type="text" name="procedure_name" id="procedure_name" required maxlength="255"
                                            value="{{ old('procedure_name') }}"
                                            placeholder="Ex : Appendicectomie"
                                            class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                        <x-input-error :messages="$errors->get('procedure_name')" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-ink" for="scheduled_at">Date et heure prévues</label>
                                        <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                                            value="{{ old('scheduled_at') }}"
                                            class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                        <x-input-error :messages="$errors->get('scheduled_at')" class="mt-1" />
                                    </div>
                                    @if ($selectedVisit->surgery_type === 'externe')
                                        <div>
                                            <label class="text-sm font-medium text-ink" for="referring_doctor_name">Médecin référent externe</label>
                                            <input type="text" name="referring_doctor_name" id="referring_doctor_name" maxlength="255"
                                                value="{{ old('referring_doctor_name') }}"
                                                placeholder="Nom du médecin ayant référé le patient"
                                                class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                            <x-input-error :messages="$errors->get('referring_doctor_name')" class="mt-1" />
                                        </div>
                                    @endif
                                    <div>
                                        <label class="text-sm font-medium text-ink" for="anesthetist_id">Anesthésiste</label>
                                        <select name="anesthetist_id" id="anesthetist_id"
                                            class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                            <option value="">— Aucun —</option>
                                            @foreach ($medecins as $medecin)
                                                <option value="{{ $medecin->id }}" @selected(old('anesthetist_id') == $medecin->id)>
                                                    {{ $medecin->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('anesthetist_id')" class="mt-1" />
                                    </div>
                                    <div class="flex justify-end border-t border-line pt-3">
                                        <x-ui.button size="sm" type="submit">Programmer</x-ui.button>
                                    </div>
                                </form>

                                @if ($selectedVisit->surgeries->isNotEmpty())
                                    <div class="flex flex-col gap-2">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Chirurgies programmées</p>
                                        @foreach ($selectedVisit->surgeries as $surgery)
                                            <div class="flex items-center justify-between rounded-md border border-line bg-surface-sunken px-3 py-2">
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-medium text-ink">{{ $surgery->procedure_name }}</p>
                                                    <p class="text-xs text-ink-muted">
                                                        @if ($surgery->scheduled_at)
                                                            {{ $surgery->scheduled_at->format('d/m/Y H:i') }}
                                                        @else
                                                            Date non définie
                                                        @endif
                                                        @if ($surgery->anesthetist)
                                                            &middot; Anesthésiste : {{ $surgery->anesthetist->name }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <x-ui.badge :tone="match($surgery->status) {
                                                        'scheduled' => 'brand',
                                                        'consent_signed' => 'warning',
                                                        'in_progress' => 'danger',
                                                        'completed' => 'success',
                                                        'cancelled' => 'neutral',
                                                        default => 'neutral',
                                                    }">
                                                        {{ match($surgery->status) {
                                                            'scheduled' => 'Programmée',
                                                            'consent_signed' => 'Consentement signé',
                                                            'in_progress' => 'En cours',
                                                            'completed' => 'Terminée',
                                                            'cancelled' => 'Annulée',
                                                            default => $surgery->status,
                                                        } }}
                                                    </x-ui.badge>
                                                    @if ($surgery->status === 'scheduled')
                                                        <form method="POST" action="{{ route('surgery.destroy', $surgery) }}"
                                                            onsubmit="return confirm('Annuler cette chirurgie ?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="rounded-md p-1 text-ink-muted hover:bg-surface-muted hover:text-danger" title="Annuler">
                                                                <x-ui.icon name="x-circle" class="h-4 w-4" />
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </x-ui.card>
                    @endif

                    <x-ui.card>
                        <h3 class="mb-2 text-sm font-semibold text-ink">Parcours de la visite</h3>
                        @if (count($timeline) > 0)
                            <x-ui.timeline :steps="$timeline" />
                        @else
                            <p class="text-sm text-ink-muted">Aucun événement enregistré pour ce patient.</p>
                        @endif
                    </x-ui.card>
                @endif
            </div>
        </div>

        {{-- Popup de confirmation avant consultation --}}
        <div x-show="confirmPopup.open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="absolute inset-0 bg-ink/40" @click="confirmPopup.open = false"></div>
            <div class="relative w-full max-w-sm rounded-lg bg-white p-6 shadow-xl"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="translate-y-4 opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50">
                        <x-ui.icon name="user-plus" class="h-5 w-5 text-brand-600" />
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-ink">Prendre en consultation</h3>
                        <p class="text-sm text-ink-muted" x-text="'Patient : ' + confirmPopup.name"></p>
                    </div>
                </div>
                <p class="mt-3 text-xs text-ink-muted">Confirmez que vous prenez ce patient en consultation. La fiche patient apparaîtra dans la colonne de droite.</p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="confirmPopup.open = false"
                            class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-soft hover:bg-surface-muted">
                        Annuler
                    </button>
                    <button type="button" @click="goToPatient()"
                            class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function medecinDashboard() {
            return {
                queue: @json($queue),
                confirmPopup: { open: false, name: '', href: '' },
                loading: false,
                lastRefresh: null,

                startPolling() {
                    this.refresh();
                    this.interval = setInterval(() => this.refresh(), 10000);
                },

                async refresh() {
                    if (this.loading) return;
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route('medecin.refresh') }}', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.queue = data.queue;
                            this.lastRefresh = new Date().toLocaleTimeString('fr-FR', {
                                hour: '2-digit', minute: '2-digit', second: '2-digit'
                            });
                        }
                    } catch (e) {
                        console.warn('Polling medecin échoué:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                confirmPatient(name, href) {
                    this.confirmPopup = { open: true, name, href };
                },

                goToPatient() {
                    window.location.href = this.confirmPopup.href;
                },

                destroy() {
                    clearInterval(this.interval);
                }
            };
        }
    </script>
</x-layout.app-shell>
