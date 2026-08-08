<x-layout.app-shell title="Liste des patients" active="gestion" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Liste des patients</h1>
            <p class="mt-1 text-sm text-ink-muted">Consultez les dossiers et l’assurance associée à chaque patient.</p>
        </div>

        <a href="{{ route('accueil.index') }}" class="inline-flex items-center justify-center rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
            <x-ui.icon name="user-plus" class="mr-2 h-4 w-4" />
            Nouveau
        </a>
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Patient</th>
                        <th class="px-5 py-3 font-semibold">Téléphone</th>
                        <th class="px-5 py-3 font-semibold">Couverture</th>
                        <th class="px-5 py-3 font-semibold">Assurance</th>
                        <th class="px-5 py-3 font-semibold">Dernière visite</th>
                        <th class="px-5 py-3 font-semibold text-right">Dossier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($patients as $patient)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5">
                                <div class="font-medium text-ink">{{ $patient->full_name }}</div>
                                <div class="text-xs text-ink-muted">{{ $patient->code }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $patient->phone ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $patient->coverage ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">{{ $patient->insuranceCompany?->name ?? 'Aucune' }}</td>
                            <td class="px-5 py-3.5 text-ink-soft">
                                @if ($patient->visits->first())
                                    {{
                                        $patient->visits->first()->arrived_at
                                            ? \Carbon\Carbon::parse($patient->visits->first()->arrived_at)->translatedFormat('d/m/Y H\:i')
                                            : '—'
                                    }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('patients.show', $patient) }}" class="inline-flex items-center rounded-md border border-line px-3 py-1.5 text-sm font-semibold text-ink-soft hover:bg-surface-muted">
                                    Voir le dossier
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-6 text-center text-ink-muted">Aucun patient enregistré pour le moment.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-layout.app-shell>
