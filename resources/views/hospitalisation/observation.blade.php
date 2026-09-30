<x-layout.app-shell title="Mise en Observation" active="hospitalisation" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-lg font-semibold text-ink">Patients en observation</h1>
            <p class="mt-1 text-sm text-ink-muted">Surveillance rapprochée — la sortie d'observation ramène le patient en consultation.</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <x-ui.stat-card label="Patients en observation" value="{{ count($patients) }}" icon="eye" trend="Actuellement" trend-tone="warning" />
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-line">
                <thead class="bg-surface-sunken">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wide text-ink-muted">Patient</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wide text-ink-muted">Chambre</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wide text-ink-muted">Unité</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wide text-ink-muted">Médecin</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wide text-ink-muted">Admis</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wide text-ink-muted">Motif</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wide text-ink-muted">Statut</th>
                        <th class="px-5 py-3 text-right text-xs uppercase tracking-wide text-ink-muted">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line bg-white">
                    @forelse ($patients as $patient)
                        <tr class="hover:bg-surface-sunken transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :initials="$patient['initials']" size="sm" tone="warning" />
                                    <div>
                                        <p class="text-sm font-medium text-ink">{{ $patient['name'] }}</p>
                                        <p class="text-xs text-ink-muted">{{ $patient['code'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-ink-soft">Ch. {{ $patient['room'] }}</td>
                            <td class="px-5 py-3.5 text-sm text-ink-soft">{{ $patient['ward'] }}</td>
                            <td class="px-5 py-3.5 text-sm text-ink-soft">{{ $patient['doctor'] }}</td>
                            <td class="px-5 py-3.5 text-sm text-ink-soft">{{ $patient['admitted'] }}</td>
                            <td class="px-5 py-3.5 text-sm text-ink-soft">{{ $patient['reason'] ?? '—' }}</td>
                            <td class="px-5 py-3.5">
                                <x-ui.badge :tone="$patient['status_tone']">{{ $patient['status'] }}</x-ui.badge>
                                <p class="mt-1 font-mono text-[11px] text-ink-muted">{{ $patient['billed_days'] }} jour(s) facturé(s)</p>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.button variant="secondary" size="sm" :href="route('patients.show', $patient['patient_id'])">Dossier</x-ui.button>

                                    @if ($patient['discharge_planned'])
                                        <form method="POST" action="{{ route('admissions.discharge', $patient['id']) }}">
                                            @csrf
                                            <x-ui.button size="sm" type="submit">Sortie d'observation</x-ui.button>
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
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-ink-muted">Aucun patient en observation actuellement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

</x-layout.app-shell>
