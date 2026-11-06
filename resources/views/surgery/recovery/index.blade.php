{{--
    Recovery Room (Salle de réveil) component.
    Expected variable: $surgery (App\Models\Surgery)
    Include with: @include("surgery.recovery.index", ["surgery" => $surgery])
--}}

@php
    $hasStarted = $surgery->recovery_started_at !== null;
    $hasEnded = $surgery->recovery_ended_at !== null;
    $nurse = $surgery->recoveryNurse?->name;
    $startedAt = $surgery->recovery_started_at;
    $endedAt = $surgery->recovery_ended_at;
@endphp

<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-ink">Salle de réveil</h3>
        @if ($hasEnded)
            <x-ui.badge tone="success">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Terminée
            </x-ui.badge>
        @elseif ($hasStarted)
            <x-ui.badge tone="warning">
                <svg class="h-3.5 w-3.5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                En cours
            </x-ui.badge>
        @else
            <x-ui.badge tone="neutral">En attente</x-ui.badge>
        @endif
    </div>

    @if (!$hasStarted)
        {{-- Start button --}}
        <form method="POST" action="{{ route("surgery.recovery.start", $surgery) }}" class="text-center py-4">
            @csrf
            <p class="text-sm text-ink-muted mb-4">Démarrer la surveillance post-anesthésique pour ce patient.</p>
            <button type="submit"
                    class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                <x-ui.icon name="play" class="h-4 w-4 mr-2" />
                Débuter la surveillance
            </button>
        </form>

    @elseif (!$hasEnded)
        {{-- Timer + End form --}}
        <div class="space-y-4" x-data="{ elapsed: 0 }" x-init="setInterval(() => elapsed++, 1000)">
            <div class="text-center">
                <p class="text-xs text-ink-muted">Temps écoulé</p>
                <p class="text-3xl font-mono font-bold text-ink" x-text="formatTime(elapsed)"></p>
            </div>

            <form method="POST" action="{{ route("surgery.recovery.end", $surgery) }}" class="space-y-4">
                @csrf

                <label for="recovery_notes" class="block text-sm font-medium text-ink mb-1">
                    Notes de surveillance (min. 10 caractères)
                </label>
                <textarea id="recovery_notes"
                          name="recovery_notes"
                          rows="5"
                          required
                          minlength="10"
                          placeholder="État du patient, constantes, douleurs, incidents..."
                          class="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>

                @error("recovery_notes")
                    <p class="text-xs text-danger">{{ $message }}</p>
                @enderror

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        <x-ui.icon name="stop" class="h-4 w-4 mr-2" />
                        Fin de surveillance
                    </button>
                </div>
            </form>
        </div>

        <script>
            function formatTime(seconds) {
                const h = String(Math.floor(seconds / 3600)).padStart(2, "0");
                const m = String(Math.floor((seconds % 3600) / 60)).padStart(2, "0");
                const s = String(seconds % 60).padStart(2, "0");
                return `${h}:${m}:${s}`;
            }
        </script>

    @else
        {{-- Read-only summary --}}
        <div class="space-y-4">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <dt class="text-ink-muted">Infirmier(e)</dt>
                <dd class="text-ink font-medium">{{ $nurse }}</dd>

                <dt class="text-ink-muted">Début</dt>
                <dd class="text-ink">{{ $startedAt->format("d/m/Y H:i") }}</dd>

                <dt class="text-ink-muted">Fin</dt>
                <dd class="text-ink">{{ $endedAt->format("d/m/Y H:i") }}</dd>

                <dt class="text-ink-muted">Durée</dt>
                <dd class="text-ink">{{ $startedAt->diffForHumans($endedAt, true) }}</dd>
            </dl>

            <div class="rounded-md border border-line bg-surface-sunken p-4">
                <p class="text-xs font-medium text-ink-muted mb-2">Notes de surveillance</p>
                <p class="text-sm text-ink whitespace-pre-line">{{ $surgery->recovery_notes }}</p>
            </div>
        </div>
    @endif
</x-ui.card>
