<x-layout.app-shell title="Bloc opératoire" active="bloc" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    @php
        $isAideSoignante = auth()->user()->role === \App\Enums\Role::AideSoignante;
        $inProgressSurgeries = $surgeries->where('status', \App\Models\Surgery::STATUS_IN_PROGRESS);
    @endphp

    <div class="space-y-6" x-data="{
        lastHash: '{{ $surgeries->pluck('status')->join(',') }}',
        init() {
            setInterval(() => {
                const params = new URLSearchParams(window.location.search);
                fetch('{{ route('bloc.refresh') }}?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => {
                    const hash = data.surgeries.map(s => s.status).join(',');
                    if (hash !== this.lastHash) { window.location.reload(); }
                })
                .catch(() => {});
            }, 15000);
        }
    }">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink">Bloc opératoire</h1>
                <p class="mt-1 text-sm text-ink-muted">Programme du {{ \Carbon\Carbon::parse($date)->translatedFormat("d F Y") }}.</p>
            </div>
        </div>

        {{-- Alert for aide-soignante: rooms to prepare --}}
        @if ($isAideSoignante && $inProgressSurgeries->isNotEmpty())
            <div class="rounded-lg border border-warning/40 bg-warning/5 p-4">
                <div class="flex items-start gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 shrink-0 text-warning mt-0.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-ink">Chambre(s) de réveil à préparer</h3>
                        <ul class="mt-2 space-y-1">
                            @foreach ($inProgressSurgeries as $s)
                                <li class="text-sm text-ink">
                                    <span class="font-medium">{{ $s->patient?->full_name ?? "—" }}</span>
                                    — {{ $s->procedure_name }}
                                    (chirurgien : {{ $s->surgeon?->name ?? "—" }})
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Filters --}}
        <x-ui.card>
            <form method="GET" action="{{ route("bloc.index") }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="filter-date" class="text-sm font-medium text-ink">Date</label>
                    <input type="date" id="filter-date" name="date" value="{{ $date }}"
                           class="mt-1.5 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                </div>

                <div>
                    <label for="filter-status" class="text-sm font-medium text-ink">Statut</label>
                    <select id="filter-status" name="status"
                            class="mt-1.5 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">Tous les statuts</option>
                        <option value="scheduled" @selected($status === "scheduled")>Programmée</option>
                        <option value="in_progress" @selected($status === "in_progress")>En cours</option>
                        <option value="completed" @selected($status === "completed")>Terminée</option>
                        <option value="cancelled" @selected($status === "cancelled")>Annulée</option>
                    </select>
                </div>

                <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <x-ui.icon name="search" class="h-4 w-4" />
                        Filtrer
                    </button>
                    <a href="{{ route("bloc.index") }}"
                       class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-sm font-medium text-ink-soft hover:bg-surface-sunken">
                        Effacer
                    </a>
                </div>
            </form>
        </x-ui.card>

        {{-- Results --}}
        <x-ui.card class="overflow-hidden !p-0">
            @if ($surgeries->isEmpty())
                <div class="flex flex-col items-center gap-2 py-12 text-center">
                    <x-ui.icon name="scissors" class="h-8 w-8 text-ink-muted" />
                    <p class="text-sm text-ink-muted">Aucune chirurgie programmée pour cette date.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-line bg-surface-sunken text-xs font-medium uppercase tracking-wider text-ink-muted">
                            <tr>
                                <th class="px-4 py-3">Heure</th>
                                <th class="px-4 py-3">Patient</th>
                                <th class="px-4 py-3">Intervention</th>
                                <th class="px-4 py-3">Chirurgien</th>
                                <th class="px-4 py-3">Anesthésiste</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Statut</th>
                                @unless ($isAideSoignante)
                                    <th class="px-4 py-3"></th>
                                @endunless
                            </tr>
                        </thead>
                        @foreach ($surgeries as $surgery)
                        <tbody class="divide-y divide-line" x-data="{ expandedConsent: false, expandedReport: false, expandedRecovery: false, expandedAudit: false }">
                                <tr class="hover:bg-surface-sunken/50">
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-ink-muted">
                                        {{ $surgery->scheduled_at ? $surgery->scheduled_at->format("H:i") : "—" }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-ink">{{ $surgery->patient?->full_name ?? "—" }}</div>
                                        <div class="text-xs text-ink-muted">{{ $surgery->patient?->code ?? "" }}</div>
                                        @if ($surgery->referring_doctor_name)
                                            <div class="text-xs text-ink-muted">Réf: {{ $surgery->referring_doctor_name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-ink">
                                        {{ $surgery->procedure_name }}
                                        @if ($surgery->cost)
                                            <div class="text-xs text-ink-muted">{{ number_format($surgery->cost, 0, ',', ' ') }} FCFA</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-ink">{{ $surgery->surgeon?->name ?? "—" }}</td>
                                    <td class="px-4 py-3 text-ink">{{ $surgery->anesthetist?->name ?? "—" }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if ($surgery->surgery_type === "interne")
                                            <x-ui.badge tone="brand">Interne</x-ui.badge>
                                        @else
                                            <x-ui.badge tone="neutral">Externe</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @switch($surgery->status)
                                            @case(\App\Models\Surgery::STATUS_SCHEDULED)
                                                <x-ui.badge tone="brand">Programmée</x-ui.badge>
                                                @break
                                            @case(\App\Models\Surgery::STATUS_IN_PROGRESS)
                                                <x-ui.badge tone="warning">En cours</x-ui.badge>
                                                @break
                                            @case(\App\Models\Surgery::STATUS_COMPLETED)
                                                <x-ui.badge tone="success">Terminée</x-ui.badge>
                                                @break
                                            @case(\App\Models\Surgery::STATUS_CANCELLED)
                                                <x-ui.badge tone="danger">Annulée</x-ui.badge>
                                                @break
                                        @endswitch
                                    </td>

                                    {{-- Actions: only for doctors --}}
                                    @unless ($isAideSoignante)
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            @if ($surgery->status === \App\Models\Surgery::STATUS_SCHEDULED)
                                                <form method="POST" action="{{ route('surgery.start', $surgery) }}"
                                                      onsubmit="return confirm('Démarrer cette chirurgie ? Le consentement sera enregistré.')">
                                                    @csrf
                                                    <input type="hidden" name="consent" value="1">
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 rounded-md bg-brand-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-brand-700">
                                                        <x-ui.icon name="play" class="h-3.5 w-3.5" />
                                                        Démarrer
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($surgery->status === \App\Models\Surgery::STATUS_IN_PROGRESS)
                                                <form method="POST" action="{{ route('surgery.complete', $surgery) }}"
                                                      onsubmit="return confirm('Terminer cette chirurgie ?')">
                                                    @csrf
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-green-700">
                                                        <x-ui.icon name="check-circle" class="h-3.5 w-3.5" />
                                                        Terminer
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($surgery->status === \App\Models\Surgery::STATUS_SCHEDULED)
                                                <form method="POST" action="{{ route('surgery.destroy', $surgery) }}"
                                                      onsubmit="return confirm('Annuler cette chirurgie ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 rounded-md border border-danger/30 px-3 py-1.5 text-xs font-medium text-danger hover:bg-danger/5">
                                                        <x-ui.icon name="x-circle" class="h-3.5 w-3.5" />
                                                        Annuler
                                                    </button>
                                                </form>
                                            @endif

                                            <button type="button" @click="expandedConsent = !expandedConsent"
                                                    class="text-xs font-medium text-brand-700 hover:text-brand-800">
                                                <span x-text="expandedConsent ? 'Masquer' : 'Pré-anesth.'"></span>
                                            </button>
                                            <button type="button" @click="expandedReport = !expandedReport"
                                                    class="text-xs font-medium text-brand-700 hover:text-brand-800">
                                                <span x-text="expandedReport ? 'Masquer' : 'Compte-rendu'"></span>
                                            </button>
                                            <button type="button" @click="expandedRecovery = !expandedRecovery"
                                                    class="text-xs font-medium text-brand-700 hover:text-brand-800">
                                                <span x-text="expandedRecovery ? 'Masquer' : 'Réveil'"></span>
                                            </button>
                                            <button type="button" @click="expandedAudit = !expandedAudit"
                                                    class="text-xs font-medium text-brand-700 hover:text-brand-800">
                                                <span x-text="expandedAudit ? 'Masquer' : 'Audit'"></span>
                                            </button>
                                        </div>
                                    </td>
                                    @endunless
                                </tr>

                                {{-- Expandable panels: only for doctors --}}
                                @unless ($isAideSoignante)
                                <tr x-show="expandedConsent" x-cloak>
                                    <td colspan="8" class="bg-surface-sunken px-4 py-4">
                                        @include("surgery.consent-section", ["surgery" => $surgery])
                                    </td>
                                </tr>
                                <tr x-show="expandedReport" x-cloak>
                                    <td colspan="8" class="bg-surface-sunken px-4 py-4">
                                        @include("surgery.operative-report.index", ["surgery" => $surgery])
                                    </td>
                                </tr>
                                <tr x-show="expandedRecovery" x-cloak>
                                    <td colspan="8" class="bg-surface-sunken px-4 py-4">
                                        @include("surgery.recovery.index", ["surgery" => $surgery])
                                    </td>
                                </tr>
                                <tr x-show="expandedAudit" x-cloak>
                                    <td colspan="8" class="bg-surface-sunken px-4 py-4">
                                        @include("surgery.audit.index", ["surgery" => $surgery])
                                    </td>
                                </tr>
                                @endunless
                        </tbody>
                        @endforeach
                    </table>
                </div>
            @endif
        </x-ui.card>

    </div>

</x-layout.app-shell>
