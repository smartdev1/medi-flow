<x-layout.app-shell title="Chambres" active="chambres" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials"
    x-data="{ filter: 'Tous les services' }"
    x-init="console.log('Alpine initialized, filter:', filter)"
    @filter-changed="filter = $event.detail.filter">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Chambres &amp; lits</h1>
            <p class="mt-1 text-sm text-ink-muted">Occupation des chambres par service, en temps réel.</p>
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="secondary" :href="route('hospitalisation.index')">
                <x-ui.icon name="document" class="h-4 w-4" />
                Patients hospitalisés
            </x-ui.button>
            <x-ui.button variant="secondary" @click="window.location.reload()">
                <x-ui.icon name="refresh-cw" class="h-4 w-4" />
                Actualiser
            </x-ui.button>
            <x-ui.slide-over title="Gestion des chambres">
                <x-slot:trigger>
                    <x-ui.button>
                        <x-ui.icon name="cog" class="h-4 w-4" />
                        Gérer les chambres
                    </x-ui.button>
                </x-slot:trigger>
                <form method="POST" action="{{ route('chambres.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <x-ui.field label="Numéro de chambre" name="number" required />
                    <x-ui.select label="Service" name="ward" :options="$wardsList" required />
                    <x-ui.field label="Capacité (nombre de lits)" name="capacity" type="number" min="1" required />
                    <label class="flex items-center gap-2 text-sm text-ink-soft">
                        <input type="checkbox" name="maintenance" value="1" class="rounded border-line text-brand focus:ring-brand">
                        En maintenance
                    </label>
                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                        <x-ui.button type="submit">Ajouter chambre</x-ui.button>
                    </div>
                </form>
            </x-ui.slide-over>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Lits totaux" value="{{ $totalBeds }}" icon="bed" trend="Toutes unités" trend-tone="neutral" />
        <x-ui.stat-card label="Lits occupés" value="{{ $occupiedBeds }}" icon="building" trend="Patients hospitalisés" trend-tone="brand" />
        <x-ui.stat-card label="Lits disponibles" value="{{ $availableBeds }}" icon="check-circle" trend="Prêts à l'admission" trend-tone="success" />
        <x-ui.stat-card label="Taux d'occupation" value="{{ $occupancyRate }}%" icon="chart-bar" trend="Capacité clinique" trend-tone="neutral" />
    </div>
    
    <x-ui.filter-pills class="mt-6" :items="$filterItems" label="Filtrer par service" />

    {{-- Debug: Vérifier les données passées à la vue --}}
    @if (config('app.debug'))
        <div class="mb-4 rounded-md border border-line bg-white p-4 text-sm">
            <strong>Debug Info:</strong><br>
            Nombre de services: {{ $wards->count() }}<br>
            @foreach ($wards as $ward)
                Service: {{ $ward['label'] }} ({{ count($ward['rooms']) }} chambres)<br>
            @endforeach
        </div>
    @endif
    
    <div class="mt-8 flex flex-col gap-8">
        @if ($wards->isEmpty())
            <div class="rounded-md border border-line bg-white p-6 text-center">
                <x-ui.icon name="building" class="mx-auto h-8 w-8 text-ink-muted" />
                <h3 class="mt-2 text-lg font-semibold text-ink">Aucune chambre trouvée</h3>
                <p class="mt-1 text-sm text-ink-muted">Commencez par ajouter des chambres en utilisant le bouton "Gérer les chambres".</p>
            </div>
        @else
            @foreach ($wards as $ward)
                <div x-show="filter === 'Tous les services' || filter === '{{ $ward['label'] }}'">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-ink-muted">{{ $ward['label'] }}</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($ward['rooms'] as $room)
                        @php
                            $occupied = count(array_filter($room['beds'], fn ($bed) => $bed['patient']));
                            $capacity = count($room['beds']);
                            $isMaintenance = $room['maintenance'] ?? false;

                            if ($isMaintenance) {
                                $statusLabel = 'Maintenance';
                                $statusTone = 'danger';
                            } elseif ($occupied === 0) {
                                $statusLabel = 'Disponible';
                                $statusTone = 'success';
                            } elseif ($occupied < $capacity) {
                                $statusLabel = 'Partiellement occupée';
                                $statusTone = 'warning';
                            } else {
                                $statusLabel = 'Complète';
                                $statusTone = 'brand';
                            }
                        @endphp
                        <x-ui.card>
                            <div class="flex items-center justify-between">
                                <h3 class="font-semibold text-ink">{{ $room['number'] }}</h3>
                                <x-ui.badge :tone="$statusTone">{{ $statusLabel }}</x-ui.badge>
                            </div>
                            <p class="mt-1 text-xs text-ink-muted">{{ $occupied }}/{{ $capacity }} lit(s) occupé(s)</p>
                            <div class="mt-2 flex gap-2">
                                <form method="POST" action="{{ route('chambres.toggle-maintenance', $room['number']) }}" onsubmit="return confirm('Êtes-vous sûr de vouloir basculer l\'état de maintenance de cette chambre ?')">
                                    @csrf
                                    @method('PATCH')
                                    <x-ui.button variant="secondary" size="xs">
                                        <x-ui.icon name="{{ $isMaintenance ? 'check-circle' : 'alert-triangle' }}" class="h-3 w-3" />
                                        {{ $isMaintenance ? 'Remettre en service' : 'Mettre en maintenance' }}
                                    </x-ui.button>
                                </form>
                                <x-ui.button variant="secondary" size="xs" @click="
                                    if (confirm('Voulez-vous vraiment supprimer cette chambre ? Cette action est irréversible.')) {
                                        fetch('{{ route('chambres.destroy', $room['number']) }}', {
                                            method: 'DELETE',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Content-Type': 'application/json'
                                            }
                                        }).then(response => {
                                            if (response.ok) {
                                                window.location.reload();
                                            } else {
                                                response.json().then(data => {
                                                    alert(data.error || 'Une erreur est survenue lors de la suppression.');
                                                });
                                            }
                                        });
                                    }
                                ">
                                    <x-ui.icon name="trash" class="h-3 w-3" />
                                    Supprimer
                                </x-ui.button>
                            </div>

                            <div class="mt-4 flex flex-col gap-2">
                                @foreach ($room['beds'] as $bed)
                                    <div class="flex items-center justify-between rounded-md border border-line bg-surface-sunken px-3 py-2">
                                        <span class="text-xs font-medium text-ink-muted">{{ $bed['label'] }}</span>
                                        @if ($bed['patient'])
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-ink">{{ $bed['patient']['name'] }}</p>
                                                <p class="font-mono text-[11px] text-ink-muted">{{ $bed['patient']['id'] }} · {{ $bed['patient']['since'] }}</p>
                                            </div>
                                        @else
                                            <span class="text-sm text-ink-muted">{{ $isMaintenance ? 'Hors service' : 'Libre' }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
            @endforeach
        @endif
    </div>

</x-layout.app-shell>
