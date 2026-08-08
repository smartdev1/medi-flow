<x-layout.app-shell title="Dossier patient - {{ $patient->full_name }}" active="hospitalisation" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Dossier médical - {{ $patient->full_name }}</h1>
            <p class="mt-1 text-sm text-ink-muted">Historique complet du patient {{ $patient->code }}</p>
        </div>
        {{-- <div class="flex gap-2">
            <x-ui.button variant="secondary" href="{{ route('hospitalisation.index') }}">
                <x-ui.icon name="arrow-left" class="h-4 w-4" />
                Retour à l'hospitalisation
            </x-ui.button>
        </div> --}}
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-6">
        <x-ui.stat-card label="Identifiant patient" value="{{ $patient->code }}" icon="identification" />
        <x-ui.stat-card label="Âge" value="{{ $patient->birth_date ? $patient->birth_date->age . ' ans' : 'Non spécifié' }}" icon="calendar" />
        <x-ui.stat-card label="Couverture" value="{{ $patient->coverage ?: 'Non spécifiée' }}" icon="shield-check" />
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 mb-6">
        <x-ui.card>
            <div class="flex items-center gap-3">
                <x-ui.avatar :initials="$patient->initials" size="lg" />
                <div>
                    <h3 class="text-lg font-semibold text-ink">{{ $patient->full_name }}</h3>
                    <p class="text-sm text-ink-muted">{{ $patient->sex }} · {{ $patient->phone ?? 'Tél. non renseigné' }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div>
                <h3 class="text-sm font-medium text-ink-muted mb-2">Informations médicales</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-ink-muted">Allergies</span>
                        <span class="text-sm font-medium">{{ $patient->allergies ?? 'Aucune connue' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-ink-muted">Personne à contacter</span>
                        <span class="text-sm font-medium">{{ $patient->emergency_contact ?? 'Non renseigné' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-ink-muted">Responsable légal</span>
                        <span class="text-sm font-medium">{{ $patient->guardian_name ?? 'Non renseigné' }}</span>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="mb-4">
        <h2 class="text-xl font-semibold text-ink">Historique médical</h2>
        <p class="text-sm text-ink-muted">Tous les événements liés à ce patient, du plus récent au plus ancien</p>
    </div>

    <div class="space-y-6">
        @forelse ($timeline as $event)
            <x-ui.card>
                <div class="flex gap-4">
                    <div class="flex-shrink-0">
                        <x-ui.icon name="{{ $event['icon'] }}" class="h-6 w-6 text-ink-soft" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="font-medium text-ink">{{ $event['title'] }}</h3>
                            <span class="text-xs font-mono text-ink-muted">{{ $event['date'] ? $event['date']->format('d/m/Y H:i') : 'Date non spécifiée' }}</span>
                        </div>
                        
                        @switch($event['type'])
                            @case('visit')
                                <div class="text-sm text-ink-soft">
                                    <p><strong>Orientation:</strong> {{ $event['details']->orientation }}</p>
                                    <p><strong>Statut:</strong> {{ $event['details']->status }}</p>
                                    @if($event['details']->reason)
                                        <p><strong>Motif:</strong> {{ $event['details']->reason }}</p>
                                    @endif
                                </div>
                                @break
                            
                            @case('consultation')
                                <div class="text-sm text-ink-soft">
                                    <p><strong>Médecin:</strong> {{ $event['details']->doctor?->name ?? 'Non spécifié' }}</p>
                                    <p><strong>Statut:</strong> {{ $event['details']->status }}</p>
                                    @if($event['details']->diagnosis)
                                        <p><strong>Diagnostic:</strong> {{ $event['details']->diagnosis }}</p>
                                    @endif
                                    @if($event['details']->treatment)
                                        <p><strong>Traitement:</strong> {{ $event['details']->treatment }}</p>
                                    @endif
                                </div>
                                @break
                            
                            @case('admission')
                                <div class="text-sm text-ink-soft">
                                    <p><strong>Chambre:</strong> {{ $event['details']->room?->number ?? 'Non spécifiée' }}</p>
                                    <p><strong>Lit:</strong> {{ $event['details']->bed_number ?? 'Non spécifié' }}</p>
                                    <p><strong>Statut:</strong> {{ $event['details']->status }}</p>
                                    @if($event['details']->discharged_at)
                                        <p><strong>Sortie:</strong> {{ $event['details']->discharged_at?->format('d/m/Y') }}</p>
                                    @endif
                                </div>
                                @break
                            
                            @case('exam')
                                <div class="text-sm text-ink-soft">
                                    <p><strong>Type:</strong> {{ $event['details']->type }}</p>
                                    <p><strong>Statut:</strong> {{ $event['details']->status }}</p>
                                    @if($event['details']->results)
                                        <p><strong>Résultats:</strong> {{ $event['details']->results }}</p>
                                    @endif
                                </div>
                                @break
                            
                            @case('prescription')
                                <div class="text-sm text-ink-soft">
                                    <p><strong>Médicaments:</strong>
                                        @foreach($event['details']->items ?? [] as $item)
                                            {{ $item->medication?->name ?? 'Médicament non spécifié' }}@if(!$loop->last), @endif
                                        @endforeach
                                    </p>
                                    <p><strong>Statut:</strong> {{ $event['details']->status }}</p>
                                </div>
                                @break
                            
                            @case('payment')
                                <div class="text-sm text-ink-soft">
                                    <p><strong>Montant:</strong> {{ $event['details']->amount }} {{ $event['details']->currency }}</p>
                                    <p><strong>Méthode:</strong> {{ $event['details']->method }}</p>
                                    <p><strong>Statut:</strong> {{ $event['details']->status }}</p>
                                </div>
                                @break
                        @endswitch
                    </div>
                </div>
            </x-ui.card>
        @empty
            <x-ui.card>
                <div class="text-center py-8">
                    <p class="text-ink-muted">Aucun historique médical trouvé pour ce patient.</p>
                </div>
            </x-ui.card>
        @endforelse
    </div>

</x-layout.app-shell>