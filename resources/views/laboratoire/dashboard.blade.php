<x-layout.app-shell title="Laboratoire" active="laboratoire" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Laboratoire</h1>
            <p class="mt-1 text-sm text-ink-muted">Prélèvements, analyses en cours et transmission des résultats.</p>
        </div>
        <x-ui.slide-over title="Nouvel examen">
            <x-slot:trigger>
                <x-ui.button>
                    <x-ui.icon name="plus" class="h-4 w-4" />
                    Nouvel examen
                </x-ui.button>
            </x-slot:trigger>

            <form method="POST" action="{{ route('exams.store') }}" class="flex flex-col gap-4">
                @csrf

                <div>
                    <x-ui.patient-search :options="$patientOptions" />
                    <x-input-error :messages="$errors->get('patient_id')" class="mt-1" />
                </div>

                <div>
                    <x-ui.lab-exam-picker />
                    <x-input-error :messages="$errors->get('items')" class="mt-1" />
                    <x-input-error :messages="$errors->get('items.*.lab_exam_id')" class="mt-1" />
                </div>

                <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                    <x-ui.button type="submit" class="w-full justify-center">Enregistrer la demande</x-ui.button>
                </div>
            </form>
        </x-ui.slide-over>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3" x-data="{
        counts: { awaiting: {{ $awaitingCount }}, inProgress: {{ $inProgressCount }}, ready: {{ $readyCount }} },
        init() {
            setInterval(() => {
                fetch('{{ route('laboratoire.refresh') }}', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => {
                    if (data.awaitingCount !== this.counts.awaiting || data.inProgressCount !== this.counts.inProgress || data.readyCount !== this.counts.ready) {
                        window.location.reload();
                    }
                })
                .catch(() => {});
            }, 15000);
        }
    }">
        <x-ui.stat-card label="Patients attendus" value="{{ $awaitingCount }}" icon="users" trend="En attente de prélèvement" trend-tone="neutral" />
        <x-ui.stat-card label="Résultats en cours" value="{{ $inProgressCount }}" icon="beaker" trend="Analyse en laboratoire" trend-tone="brand" />
        <x-ui.stat-card label="Résultats prêts" value="{{ $readyCount }}" icon="check-circle" trend="À transmettre" trend-tone="success" />
    </div>
    
    @if($showDebugInfo)
        <x-ui.card class="mt-4 bg-yellow-50 border-yellow-200">
            <div class="flex items-start gap-3">
                <x-ui.icon name="information-circle" class="h-5 w-5 text-yellow-600 mt-0.5" />
                <div class="flex-1">
                    <h3 class="font-medium text-yellow-800">Aucun examen trouvé</h3>
                    <p class="mt-1 text-sm text-yellow-700">
                        Nous avons recherché les examens des 30 derniers jours, mais aucun n'a été trouvé. 
                        Cela peut signifier :
                    </p>
                    <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                        <li>Aucun examen n'a encore été enregistré dans le système</li>
                        <li>Tous les examens ont été marqués comme "Envoyés au médecin"</li>
                        <li>Les examens existent mais ont des dates incorrectes</li>
                    </ul>
                    <p class="mt-2 text-sm text-yellow-700">
                        <strong>Solution:</strong> Utilisez le bouton "Nouvel examen" ci-dessus pour créer un nouvel examen.
                    </p>
                </div>
            </div>
        </x-ui.card>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.queue-list title="Patients attendus" :items="$queue" class="lg:col-span-1" />

        <x-ui.card :padded="false" class="lg:col-span-2 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                            <th class="px-5 py-3 font-semibold">Patient</th>
                            <th class="px-5 py-3 font-semibold">Examen</th>
                            <th class="px-5 py-3 font-semibold">Demandé le</th>
                            <th class="px-5 py-3 font-semibold">Statut</th>
                            <th class="px-5 py-3 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($exams as $exam)
                            <tr class="hover:bg-surface-sunken">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <x-ui.avatar :initials="$exam->patient->initials" size="sm" />
                                        <div>
                                            <p class="font-medium text-ink">{{ $exam->patient->full_name }}</p>
                                            <p class="font-mono text-xs text-ink-muted">{{ $exam->patient->code }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-ink-soft">
                                    <p>{{ $exam->name }}</p>
                                    @if($exam->result)
                                        <p class="mt-1 text-xs text-ink-muted">Résultat: {{ Str::limit($exam->result, 30) }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-ink-soft">
                                    <p>{{ $exam->requested_at->format('d/m/Y') }}</p>
                                    <p class="text-xs text-ink-muted">{{ $exam->requested_at->format('H:i') }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <x-ui.badge :tone="$exam->status_tone">{{ $exam->status }}</x-ui.badge>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @if ($exam->next_action)
                                        <form method="POST" action="{{ route('exams.advance', $exam) }}">
                                            @csrf
                                            <x-ui.button variant="secondary" size="sm" type="submit">{{ $exam->next_action }}</x-ui.button>
                                        </form>
                                    @else
                                        <span class="text-xs text-ink-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <x-ui.icon name="microscope" class="h-8 w-8 text-ink-muted" />
                                        <p class="text-sm text-ink-muted">Aucun examen trouvé pour la période sélectionnée</p>
                                        @if($showDebugInfo)
                                            <p class="text-xs text-ink-muted">Utilisez le bouton "Nouvel examen" pour créer un nouvel examen</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

</x-layout.app-shell>
