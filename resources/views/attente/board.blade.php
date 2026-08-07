<x-layout.app-shell title="Salle d'attente" active="attente" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Circuit patient — vue d'ensemble</h1>
            <p class="mt-1 text-sm text-ink-muted">Position de chaque patient dans le parcours de soins, en temps réel.</p>
        </div>
        <x-ui.badge tone="brand">
            <x-ui.icon name="clock" class="h-3.5 w-3.5" />
            Actualisé à l'instant
        </x-ui.badge>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
        @foreach ($stages as $stage)
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between px-1">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                        <x-ui.icon :name="$stage['icon']" class="h-4 w-4 text-ink-muted" />
                        {{ $stage['label'] }}
                    </div>
                    <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-surface-muted px-1.5 text-xs font-semibold text-ink-soft">
                        {{ count($stage['patients']) }}
                    </span>
                </div>

                <div class="flex flex-col gap-2">
                    @forelse ($stage['patients'] as $patient)
                        <x-ui.circuit-card
                            :name="$patient['name']"
                            :id="$patient['id']"
                            :patient-id="$patient['patient_id']"
                            :meta="$patient['meta']"
                            :tone="$patient['tone']"
                            :badge="$patient['badge'] ?? null"
                        />
                    @empty
                        <div class="rounded-md border border-dashed border-line px-3 py-4 text-center text-xs text-ink-muted">
                            Aucun patient
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

</x-layout.app-shell>
