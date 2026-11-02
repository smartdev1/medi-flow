<x-layout.app-shell title="État quotidien" active="etat-quotidien" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">État quotidien</h1>
            <p class="mt-1 text-sm text-ink-muted">Suivi des patients pour le {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}.</p>
        </div>
        <a href="{{ route('etat-quotidien.export-csv', request()->query()) }}"
           class="inline-flex items-center gap-2 rounded-md border border-line bg-white px-4 py-2 text-sm font-medium text-ink shadow-sm hover:bg-surface-sunken">
            <x-ui.icon name="document" class="h-4 w-4" />
            Exporter CSV
        </a>
    </div>

    {{-- Filters --}}
    <x-ui.card class="mt-6">
        <form method="GET" action="{{ route('etat-quotidien.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="filter-date" class="text-sm font-medium text-ink">Date</label>
                <input type="date" id="filter-date" name="date" value="{{ $date }}"
                       class="mt-1.5 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
            </div>

            <div>
                <label for="filter-service" class="text-sm font-medium text-ink">Service</label>
                <select id="filter-service" name="service"
                        class="mt-1.5 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">Tous les services</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->value }}" @selected($service === $cat->value)>{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-statut" class="text-sm font-medium text-ink">Statut</label>
                <select id="filter-statut" name="statut"
                        class="mt-1.5 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected($statut === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-search" class="text-sm font-medium text-ink">Recherche</label>
                <input type="text" id="filter-search" name="search" value="{{ $search }}" placeholder="Nom ou code patient..."
                       class="mt-1.5 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    <x-ui.icon name="search" class="h-4 w-4" />
                    Filtrer
                </button>
                <a href="{{ route('etat-quotidien.index') }}"
                   class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm font-medium text-ink-soft hover:bg-surface-sunken">
                    Effacer
                </a>
            </div>
        </form>
    </x-ui.card>

    {{-- Results table --}}
    <x-ui.card class="mt-6 overflow-hidden !p-0">
        @if ($visits->isEmpty())
            <div class="flex flex-col items-center gap-2 py-12 text-center">
                <x-ui.icon name="users" class="h-8 w-8 text-ink-muted" />
                <p class="text-sm text-ink-muted">Aucun patient pour cette période.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line bg-surface-sunken text-xs font-medium uppercase tracking-wider text-ink-muted">
                        <tr>
                            <th class="px-4 py-3">Patient</th>
                            <th class="px-4 py-3">Service</th>
                            <th class="px-4 py-3">Heure d'arrivée</th>
                            <th class="px-4 py-3">Heure de départ</th>
                            <th class="px-4 py-3">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($visits as $visit)
                            <tr class="hover:bg-surface-sunken/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-ink">{{ $visit->patient->full_name }}</div>
                                    <div class="text-xs text-ink-muted">{{ $visit->patient->code }}</div>
                                </td>
                                <td class="px-4 py-3 text-ink-soft">{{ $visit->act?->category?->label() ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $visit->arrived_at?->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $visit->discharged_at?->format('H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <x-ui.badge :tone="$visit->status_tone">{{ $visit->status }}</x-ui.badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($visits->hasPages())
                <div class="border-t border-line px-4 py-3">
                    {{ $visits->links() }}
                </div>
            @endif
        @endif
    </x-ui.card>

</x-layout.app-shell>
