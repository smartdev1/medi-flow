<x-layout.app-shell title="Mise en Observation" active="observation" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Mise en Observation</h1>
            <p class="mt-1 text-sm text-ink-muted">Suivi des patients sous observation médicale.</p>
        </div>
    </div>

    <div class="mt-6">
        <x-ui.card :padded="false" class="overflow-hidden">
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
                        @forelse ($observations as $patient)
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
                                    <div class="flex justify-end gap-2">
                                        <x-ui.button variant="secondary" size="sm" :href="route('patients.show', $patient['patient_id'])">Voir dossier</x-ui.button>
                                        <form method="POST" action="{{ route('admissions.discharge', $patient['id']) }}">
                                            @csrf
                                            <x-ui.button size="sm" type="submit">Programmer la sortie</x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-sm text-ink-muted">Aucun patient en observation pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

</x-layout.app-shell>
