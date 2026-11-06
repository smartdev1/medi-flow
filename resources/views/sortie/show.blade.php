<x-layout.app-shell title="Sortie patient" active="sortie" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Sortie patient</h1>
            <p class="mt-1 text-sm text-ink-muted">Recherchez un patient pour consulter son résumé et clôturer sa visite.</p>
        </div>
    </div>

    <x-ui.card class="mt-6">
        <form method="GET" action="{{ route('sortie.index') }}" x-data="{
                query: @js($patient ? $patient->code.' — '.$patient->full_name : ''),
                open: false,
                selectedId: @js($patient?->id),
                options: @js($patientOptions->map(fn ($p) => ['id' => $p->id, 'code' => $p->code, 'name' => $p->full_name])),
                get filtered() {
                    const q = this.query.trim().toLowerCase();
                    if (! q) return this.options;
                    return this.options.filter(o => 
                        o.code.toLowerCase().includes(q) || 
                        o.name.toLowerCase().includes(q)
                    );
                },
                select(option) {
                    this.selectedId = option.id;
                    this.query = option.code + ' — ' + option.name;
                    this.open = false;
                    setTimeout(() => this.$refs.form.submit(), 50);
                },
            }"
            @click.outside="open = false"
            x-ref="form"
            class="relative"
        >
            <input type="hidden" name="patient" :value="selectedId">
            <label class="text-sm font-medium text-ink">Rechercher un patient</label>
            <div class="relative mt-1.5">
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
                    <x-ui.icon name="search" class="h-4 w-4" />
                </span>
                <input
                    type="text"
                    x-model="query"
                    @focus="open = true"
                    @input="open = true"
                    placeholder="Rechercher par CSM-XXXX ou par nom..."
                    autocomplete="off"
                    class="w-full rounded-md border border-line bg-white py-2.5 pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                >
            </div>
            <div
                x-show="open"
                x-cloak
                class="absolute top-full z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-line bg-white py-1 shadow-card-hover"
            >
                <template x-for="option in filtered" :key="option.id">
                    <button type="button" @click="select(option)" class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-surface-sunken">
                        <span class="font-medium text-ink" x-text="option.name"></span>
                        <span class="font-mono text-xs text-ink-muted" x-text="option.code"></span>
                    </button>
                </template>
                <template x-if="filtered.length === 0 && query.length > 0">
                    <div class="px-3 py-2 text-center text-sm text-ink-muted">
                        Aucun patient trouvé pour "<span x-text="query"></span>"
                    </div>
                </template>
                <template x-if="filtered.length === 0 && query.length === 0">
                    <div class="px-3 py-2 text-center text-sm text-ink-muted">
                        Commencez à taper pour rechercher un patient...
                    </div>
                </template>
            </div>
        </form>
    </x-ui.card>

    @if (!$patient)
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @if ($patientsReadyForDischarge->isEmpty())
                    <x-ui.card class="flex flex-col items-center gap-2 py-12 text-center">
                        <x-ui.icon name="search" class="h-8 w-8 text-ink-muted" />
                        <p class="font-medium text-ink">Aucun patient prêt pour la sortie</p>
                        <p class="text-sm text-ink-muted">Tous les patients ont été clôturés.</p>
                    </x-ui.card>
                @else
                    <x-ui.card>
                        <h3 class="text-sm font-semibold text-ink">Patients prêts pour la sortie</h3>
                        <p class="mt-1 text-xs text-ink-muted">Sélectionnez un patient pour clôturer sa visite</p>
                        
                        <div class="mt-3 divide-y divide-line">
                            @foreach ($patientsReadyForDischarge as $readyPatient)
                                <a href="{{ route('sortie.index', ['patient' => $readyPatient->id]) }}" class="block py-3 hover:bg-surface-sunken">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <x-ui.avatar :initials="$readyPatient->initials" size="sm" />
                                            <div>
                                                <p class="font-medium text-ink">{{ $readyPatient->full_name }}</p>
                                                <p class="text-xs font-mono text-ink-muted">{{ $readyPatient->code }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-ink-soft">
                                                @php
                                                    $lastVisit = $readyPatient->visits->whereNull('discharged_at')->sortByDesc('arrived_at')->first();
                                                @endphp
                                                @if($lastVisit)
                                                    Arrivé {{ $lastVisit->arrived_at->diffForHumans() }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endif
            </div>
            
            <div class="lg:col-span-1">
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink">Recherche avancée</h3>
                    <p class="mt-1 text-xs text-ink-muted">Utilisez la recherche ci-dessus pour trouver un patient spécifique</p>
                </x-ui.card>
            </div>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <x-ui.card>
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <x-ui.avatar :initials="$patient->initials" size="lg" />
                            <div>
                                <h2 class="text-lg font-semibold text-ink">{{ $patient->full_name }}</h2>
                                <p class="font-mono text-xs text-ink-muted">{{ $patient->code }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <x-ui.badge tone="neutral">{{ $patient->birth_date?->age ?? '—' }} ans · {{ $patient->sex }}</x-ui.badge>
                                    <x-ui.badge tone="neutral">{{ $patient->coverage }}</x-ui.badge>
                                    @if ($activeVisit)
                                        <x-ui.badge tone="warning">Visite en cours</x-ui.badge>
                                    @else
                                        <x-ui.badge tone="success">Dossier clôturé</x-ui.badge>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($activeVisit)
                            @php
                                $canClose = !$depiquagePending && !$activeInvoice;
                            @endphp
                            @if ($depiquagePending)
                                <div class="mb-3 flex items-center gap-2 rounded-md border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-800">
                                    <x-ui.icon name="alert-triangle" class="h-4 w-4 flex-shrink-0" />
                                    En attente de validation du dépiquage par les aides-soignantes
                                </div>
                            @endif
                            @if ($activeInvoice)
                                <div class="mb-3 flex items-center gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800">
                                    <x-ui.icon name="x-circle" class="h-4 w-4 flex-shrink-0" />
                                    Facture impayée : {{ number_format($activeInvoice->remaining_amount, 0, ',', ' ') }} FCFA restant(s)
                                </div>
                            @endif
                            <form method="POST" action="{{ route('sortie.close', $activeVisit) }}">
                                @csrf
                                <x-ui.button type="submit" :disabled="!$canClose">
                                    <x-ui.icon name="check-circle" class="h-4 w-4" />
                                    Clôturer le dossier
                                </x-ui.button>
                            </form>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink">Paiements</h3>
                    @if ($patient->payments->isEmpty())
                        <p class="mt-3 text-sm text-ink-muted">Aucun paiement enregistré.</p>
                    @else
                        <div class="mt-3 divide-y divide-line">
                            @foreach ($patient->payments as $payment)
                                <div class="flex items-center justify-between py-2.5 text-sm">
                                    <span class="text-ink-soft">{{ $payment->service }}</span>
                                    <div class="flex items-center gap-3">
                                        <span class="font-mono font-medium text-ink">{{ $payment->formatted_amount }}</span>
                                        <x-ui.badge :tone="$payment->status_tone">{{ $payment->status }}</x-ui.badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink">Consultations</h3>
                    @if ($patient->consultations->isEmpty())
                        <p class="mt-3 text-sm text-ink-muted">Aucune consultation enregistrée.</p>
                    @else
                        <div class="mt-3 flex flex-col gap-3">
                            @foreach ($patient->consultations as $consultation)
                                <div class="rounded-md border border-line bg-surface-sunken px-3 py-3">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-ink">{{ $consultation->doctor->name ?? 'Médecin' }}</p>
                                        <x-ui.badge :tone="$consultation->status_tone">{{ $consultation->status }}</x-ui.badge>
                                    </div>
                                    @if ($consultation->notes)
                                        <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ $consultation->notes }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink">Examens de laboratoire</h3>
                    @if ($patient->exams->isEmpty())
                        <p class="mt-3 text-sm text-ink-muted">Aucun examen demandé.</p>
                    @else
                        <div class="mt-3 divide-y divide-line">
                            @foreach ($patient->exams as $exam)
                                <div class="flex items-center justify-between py-2.5 text-sm">
                                    <span class="text-ink-soft">{{ $exam->name }}</span>
                                    <x-ui.badge :tone="$exam->status_tone">{{ $exam->status }}</x-ui.badge>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="text-sm font-semibold text-ink">Ordonnances &amp; délivrance</h3>
                    @if ($patient->prescriptions->isEmpty())
                        <p class="mt-3 text-sm text-ink-muted">Aucune ordonnance enregistrée.</p>
                    @else
                        <div class="mt-3 divide-y divide-line">
                            @foreach ($patient->prescriptions as $prescription)
                                <div class="flex items-center justify-between py-2.5 text-sm">
                                    <span class="text-ink-soft">{{ $prescription->medications_summary }}</span>
                                    <x-ui.badge :tone="$prescription->status_tone">{{ $prescription->status }}</x-ui.badge>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            </div>

            <x-ui.card class="lg:col-span-1 h-fit">
                <h3 class="mb-2 text-sm font-semibold text-ink">Parcours complet</h3>
                @if (count($timeline) > 0)
                    <x-ui.timeline :steps="$timeline" />
                @else
                    <p class="text-sm text-ink-muted">Aucun événement enregistré.</p>
                @endif
            </x-ui.card>
        </div>
    @endif

</x-layout.app-shell>
