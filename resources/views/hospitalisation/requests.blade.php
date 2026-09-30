<x-layout.app-shell title="Demandes d'admission" active="hospitalisation" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Demandes d'admission</h1>
            <p class="mt-1 text-sm text-ink-muted">Validez les demandes d'hospitalisation ou de mise en observation.</p>
        </div>
        <div class="flex gap-2">
            <x-ui.button variant="secondary" :href="route('hospitalisation.index')">
                <x-ui.icon name="building" class="h-4 w-4" />
                Hospitalisations
            </x-ui.button>
        </div>
    </div>

    <div class="mt-6">
        @if ($requests->isEmpty())
            <x-ui.card class="flex flex-col items-center gap-2 py-12 text-center">
                <x-ui.icon name="check-circle" class="h-8 w-8 text-success" />
                <p class="font-medium text-ink">Aucune demande en attente</p>
                <p class="text-sm text-ink-muted">Toutes les demandes ont été traitées.</p>
            </x-ui.card>
        @else
            <div class="flex flex-col gap-4">
                @foreach ($requests as $req)
                    <x-ui.card x-data="{{ json_encode(['selectedRoom' => '', 'allRooms' => $rooms]) }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-4">
                                <x-ui.avatar :initials="$req->patient->initials" size="lg" />
                                <div>
                                    <h2 class="text-lg font-semibold text-ink">{{ $req->patient->full_name }}</h2>
                                    <p class="font-mono text-xs text-ink-muted">{{ $req->patient->code }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <x-ui.badge :tone="$req->isObservation() ? 'warning' : 'danger'">
                                            {{ $req->isObservation() ? 'Mise en Observation' : 'Hospitalisation' }}
                                        </x-ui.badge>
                                        <x-ui.badge tone="neutral">Demandé par {{ $req->requester?->name }}</x-ui.badge>
                                        <x-ui.badge tone="neutral">{{ $req->created_at->diffForHumans() }}</x-ui.badge>
                                    </div>
                                    @if ($req->reason)
                                        <p class="mt-2 text-sm text-ink-muted">Motif : {{ $req->reason }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex shrink-0 flex-col gap-2">
                                <form method="POST" action="{{ route('admission-requests.approve', $req) }}" class="flex flex-col gap-2">
                                    @csrf
                                    <div class="flex gap-2">
                                        <select name="room_id" required x-model="selectedRoom" class="rounded-md border border-line bg-white px-3 py-2 text-sm">
                                            <option value="">Chambre</option>
                                            @foreach ($rooms as $room)
                                                <option value="{{ $room['id'] }}">{{ $room['label'] }}</option>
                                            @endforeach
                                        </select>
                                        <select name="bed_number" required :disabled="!selectedRoom" class="rounded-md border border-line bg-white px-3 py-2 text-sm">
                                            <option value="">Lit</option>
                                            <template x-for="bed in (selectedRoom ? (allRooms.find(r => r.id == selectedRoom)?.beds || []) : [])" :key="bed">
                                                <option :value="bed" x-text="'Lit ' + bed"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <x-ui.button type="submit" class="w-full justify-center">
                                        <x-ui.icon name="check" class="h-4 w-4" />
                                        Approuver
                                    </x-ui.button>
                                </form>
                                <form method="POST" action="{{ route('admission-requests.reject', $req) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="danger" class="w-full justify-center">
                                        <x-ui.icon name="x-circle" class="h-4 w-4" />
                                        Rejeter
                                    </x-ui.button>
                                </form>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @endif
    </div>

</x-layout.app-shell>
