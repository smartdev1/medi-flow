<x-layout.app-shell title="Salle d'attente" active="attente" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div x-data="attenteBoard()" x-init="startPolling()" class="w-full">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-ink">Circuit patient — vue d'ensemble</h1>
                <p class="mt-1 text-sm text-ink-muted">Position de chaque patient dans le parcours de soins, en temps
                    réel.</p>
            </div>
            <div class="flex items-center gap-3">
                <x-ui.badge tone="brand">
                    <x-ui.icon name="clock" class="h-3.5 w-3.5" />
                    <span x-text="lastRefresh ? 'Dernière MAJ : ' + lastRefresh : 'Chargement...'"></span>
                </x-ui.badge>
                <button type="button" @click="refresh()"
                    class="flex items-center gap-1.5 rounded-md border border-line px-3 py-1.5 text-sm text-ink-soft hover:bg-surface-muted"
                    :disabled="loading">
                    <template x-if="loading">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 animate-spin">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 9 9" />
                        </svg>
                    </template>
                    <template x-if="!loading">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-2.636-6.364M21 3v6h-6" />
                        </svg>
                    </template>
                    <span>Rafraîchir</span>
                </button>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
            <template x-for="stage in stages" :key="stage.label">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-1.5 text-sm font-semibold text-ink">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 text-ink-muted" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="iconPaths[stage.icon] || iconPaths['x-circle']" />
                            </svg>
                            <span x-text="stage.label"></span>
                        </div>
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-surface-muted px-1.5 text-xs font-semibold text-ink-soft"
                              x-text="stage.patients.length"></span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <template x-for="patient in stage.patients" :key="patient.patient_id || patient.id">
                            <div class="rounded-md border border-line border-l-4 bg-white p-3 shadow-card"
                                :class="stage.label === 'Consultation' ? (
                                    patient.vitals_taken_at ? 'border-l-success' : (patient.tone === 'danger' ? 'border-l-danger' : 'border-l-brand-500')
                                ) : (
                                    (patient.tone === 'brand') ? 'border-l-brand-500' :
                                    (patient.tone === 'success') ? 'border-l-success' :
                                    (patient.tone === 'warning') ? 'border-l-warning' :
                                    (patient.tone === 'danger') ? 'border-l-danger' : 'border-l-line'
                                )">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium leading-tight text-ink" x-text="patient.name"></p>
                                    <template x-if="patient.badge">
                                        <span class="inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-medium leading-none shrink-0"
                                            :class="{
                                                'bg-surface-muted text-ink-soft border-line': patient.badge.tone === 'neutral',
                                                'bg-brand-50 text-brand-700 border-brand-200': patient.badge.tone === 'brand',
                                                'bg-success-tint text-success border-success/20': patient.badge.tone === 'success',
                                                'bg-warning-tint text-warning border-warning/20': patient.badge.tone === 'warning',
                                                'bg-danger-tint text-danger border-danger/20': patient.badge.tone === 'danger'
                                            }">
                                            <span x-text="patient.badge.label"></span>
                                        </span>
                                    </template>
                                </div>
                                <p class="mt-1 font-mono text-[11px] text-ink-muted" x-text="patient.id"></p>

                                <template x-if="stage.label === 'Consultation' || patient.meta">
                                    <p class="text-xs text-ink-soft"
                                       :class="stage.label === 'Consultation' ? 'mt-1' : 'mt-1.5'"
                                       x-text="patient.meta"></p>
                                </template>

                                <template x-if="stage.label === 'Consultation'">
                                    <template x-if="patient.vitals_taken_at">
                                        <div x-show="!canTakeVitals" class="mt-2 flex items-center gap-2 text-xs font-medium text-success">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="'Constantes prises par ' + patient.vitals_taken_by"></span>
                                        </div>
                                        <label x-show="canTakeVitals" class="mt-2 flex cursor-not-allowed items-center gap-2 text-xs font-medium text-success">
                                            <input type="checkbox" checked disabled class="h-4 w-4 rounded border-line text-brand-500">
                                            <span x-text="'Constantes prises par ' + patient.vitals_taken_by"></span>
                                        </label>
                                    </template>
                                    <template x-if="!patient.vitals_taken_at && canTakeVitals">
                                        <form method="POST" :action="'/visites/' + patient.visit_id + '/constantes'" class="mt-2">
                                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                            <label class="flex cursor-pointer items-center gap-2 text-xs font-medium text-ink-soft hover:text-ink">
                                                <input type="checkbox" class="h-4 w-4 rounded border-line text-brand-500 focus:ring-brand-500"
                                                    @change="if ($event.target.checked) { if (confirm('Confirmer que les constantes du patient ' + patient.id + ' ont été prises ?')) { $event.target.closest('form').submit(); } else { $event.target.checked = false; } }">
                                                Confirmer les constantes
                                            </label>
                                        </form>
                                    </template>
                                </template>
                            </div>
                        </template>
                        <div x-show="!stage.patients || stage.patients.length === 0" class="rounded-md border border-dashed border-line px-3 py-4 text-center text-xs text-ink-muted">
                            Aucun patient
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <script>
        function attenteBoard() {
            const iconPaths = {
                'grid': 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
                'stethoscope': 'M8.25 3v3.75A2.25 2.25 0 0 0 10.5 9h1.5m0 0V3m0 6v4.5a4.5 4.5 0 1 0 9 0V12m-9 1.5h9m-13.5 6a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z',
                'beaker': 'M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5.858 13.64a2.25 2.25 0 0 0-.659 1.591v.114a6 6 0 0 0 6 6h1.5a6 6 0 0 0 6-6v-.114a2.25 2.25 0 0 0-.659-1.591l-3.234-3.234a2.25 2.25 0 0 1-.659-1.591V3.104M7.5 3h9M8.25 15h7.5',
                'document': 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.519-3.769L12 15m1.481-1.519L12 12m3 2.25v-4.5m-9 8.25h9a2.25 2.25 0 0 0 2.25-2.25v-6.75a1.125 1.125 0 0 0-.33-.795l-4.875-4.875a1.125 1.125 0 0 0-.795-.33H6.75A2.25 2.25 0 0 0 4.5 4.5v13.5a2.25 2.25 0 0 0 2.25 2.25Z',
                'check-circle': 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                'eye': 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
                'building': 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
            };

            return {
                stages: @json($stages),
                iconPaths: iconPaths,
                canTakeVitals: @json($canTakeVitals),
                loading: false,
                lastRefresh: null,

                isConsultation(label) {
                    return label === 'Consultation';
                },

                startPolling() {
                    this.refresh();
                    this.interval = setInterval(() => this.refresh(), 10000);
                },

                async refresh() {
                    if (this.loading) return;
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route('attente.refresh') }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.stages = data.stages;
                            this.canTakeVitals = data.canTakeVitals;
                            this.lastRefresh = new Date().toLocaleTimeString('fr-FR', {
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit'
                            });
                        }
                    } catch (e) {
                        console.warn('Polling attente échoué:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                destroy() {
                    clearInterval(this.interval);
                }
            };
        }
    </script>
</x-layout.app-shell>
