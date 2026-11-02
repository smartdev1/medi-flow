<x-layout.app-shell title="Bloc opératoire" active="bloc" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink">Bloc opératoire</h1>
                <p class="mt-1 text-sm text-ink-muted">Programme du {{ \Carbon\Carbon::parse($date)->translatedFormat("d F Y") }}.</p>
            </div>
        </div>

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
                        <option value="consent_signed" @selected($status === "consent_signed")>Consentement signé</option>
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
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        @foreach ($surgeries as $surgery)
                        <tbody class="divide-y divide-line" x-data="{ expandedConsent: false, expandedChecklist: false, expandedReport: false, expandedRecovery: false, expandedAudit: false }">
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
                                    <td class="px-4 py-3 text-ink">{{ $surgery->procedure_name }}</td>
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
                                            @case(\App\Models\Surgery::STATUS_CONSENT_SIGNED)
                                                <x-ui.badge tone="success">Consentement signé</x-ui.badge>
                                                @break
                                            @case(\App\Models\Surgery::STATUS_IN_PROGRESS)
                                                <x-ui.badge tone="warning">En cours</x-ui.badge>
                                                @break
                                            @case(\App\Models\Surgery::STATUS_COMPLETED)
                                                <x-ui.badge tone="neutral">Terminée</x-ui.badge>
                                                @break
                                            @case(\App\Models\Surgery::STATUS_CANCELLED)
                                                <x-ui.badge tone="danger">Annulée</x-ui.badge>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button type="button" @click="expandedConsent = !expandedConsent"
                                                class="text-sm font-medium text-brand-700 hover:text-brand-800 mr-2">
                                            <span x-text="expandedConsent ? 'Masquer' : 'Consentement'"></span>
                                        </button>
                                        <button type="button" @click="expandedChecklist = !expandedChecklist"
                                                class="text-sm font-medium text-brand-700 hover:text-brand-800 mr-2">
                                            <span x-text="expandedChecklist ? 'Masquer' : 'Checklist'"></span>
                                        </button>
                                        <button type="button" @click="expandedReport = !expandedReport"
                                                class="text-sm font-medium text-brand-700 hover:text-brand-800 mr-2">
                                            <span x-text="expandedReport ? 'Masquer' : 'Compte-rendu'"></span>
                                        </button>
                                        <button type="button" @click="expandedRecovery = !expandedRecovery"
                                                class="text-sm font-medium text-brand-700 hover:text-brand-800 mr-2">
                                            <span x-text="expandedRecovery ? 'Masquer' : 'Réveil'"></span>
                                        </button>
                                        <button type="button" @click="expandedAudit = !expandedAudit"
                                                class="text-sm font-medium text-brand-700 hover:text-brand-800">
                                            <span x-text="expandedAudit ? 'Masquer' : 'Audit'"></span>
                                        </button>
                                    </td>
                                </tr>
                                <tr x-show="expandedConsent" x-cloak>
                                    <td colspan="8" class="bg-surface-sunken px-4 py-4">
                                        @include("surgery.consent-section", ["surgery" => $surgery])
                                    </td>
                                </tr>
                                <tr x-show="expandedChecklist" x-cloak>
                                    <td colspan="8" class="bg-surface-sunken px-4 py-4">
                                        @include("surgery.checklist.index", ["surgery" => $surgery])
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
                        </tbody>
                        @endforeach
                    </table>
                </div>
            @endif
        </x-ui.card>

    </div>

</x-layout.app-shell>
