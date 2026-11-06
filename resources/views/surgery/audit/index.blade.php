{{--
    Surgery Audit Trail (Historique d'audit) component.
    Expected variable: $surgery (App\Models\Surgery)
    Include with: @include("surgery.audit.index", ["surgery" => $surgery])
--}}

@php
    $logs = $surgery->auditLogs()->with("user")->get();
    $actionLabels = [
        "created" => "Création",
        "updated" => "Modification",
        "status_changed" => "Changement de statut",
        "pre_anesthetic_notes_filled" => "Notes pré-anesthésiques",
        "cost_updated" => "Montant modifié",
        "report_submitted" => "Compte-rendu soumis",
        "recovery_started" => "Réveil démarré",
        "recovery_ended" => "Réveil terminé",
    ];
    $actionTones = [
        "created" => "brand",
        "updated" => "info",
        "status_changed" => "warning",
        "pre_anesthetic_notes_filled" => "info",
        "cost_updated" => "warning",
        "report_submitted" => "brand",
        "recovery_started" => "info",
        "recovery_ended" => "success",
    ];
@endphp

<x-ui.card>
    <h3 class="mb-4 text-sm font-semibold text-ink">Historique d'audit</h3>

    @if ($logs->isEmpty())
        <div class="text-center py-8 text-sm text-ink-muted">
            <x-ui.icon name="clock" class="h-8 w-8 mx-auto text-ink-muted" />
            <p class="mt-2">Aucune entrée d'audit pour cette chirurgie.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($logs as $log)
                @php
                    $actionLabel = $actionLabels[$log->action] ?? ucfirst(str_replace("_", " ", $log->action));
                    $actionTone = $actionTones[$log->action] ?? "neutral";
                    $userName = $log->user?->name ?? "Système";
                    $userInitials = $log->user?->initials ?? "SYS";
                @endphp

                <div class="flex flex-col gap-2 rounded-md border border-line bg-white p-4">
                    {{-- Header: action, user, date --}}
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <x-ui.badge :tone="$actionTone">{{ $actionLabel }}</x-ui.badge>
                            <span class="text-xs text-ink-muted">
                                par <span class="font-medium text-ink">{{ $userName }}</span>
                            </span>
                        </div>
                        <span class="text-xs font-mono text-ink-muted">{{ $log->created_at->format("d/m/Y H:i:s") }}</span>
                    </div>

                    {{-- Details: old/new values --}}
                    @if ($log->old_values || $log->new_values)
                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            @if ($log->old_values)
                                <div class="rounded-md border border-line bg-surface-sunken p-3">
                                    <p class="text-xs font-medium text-ink-muted mb-1">Avant</p>
                                    <pre class="text-xs text-ink whitespace-pre-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif

                            @if ($log->new_values)
                                <div class="rounded-md border border-success/30 bg-success-tint p-3">
                                    <p class="text-xs font-medium text-success mb-1">Après</p>
                                    <pre class="text-xs text-ink whitespace-pre-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- IP address if available --}}
                    @if ($log->ip_address)
                        <div class="text-xs text-ink-muted">
                            <svg class="inline h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            IP: {{ $log->ip_address }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-ui.card>
