<x-layout.app-shell title="Espace Médecin" active="medecin" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Espace Médecin</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ count($queue) }} patient(s) en attente de consultation aujourd'hui.</p>
        </div>
        <div class="flex items-center gap-2">
            @if ($isOnCallToday)
                <x-ui.badge tone="danger">
                    <x-ui.icon name="alert-triangle" class="h-3.5 w-3.5" />
                    De garde aujourd'hui
                </x-ui.badge>
            @endif
            <x-ui.badge tone="brand">{{ $userName }} · {{ $userRole }}</x-ui.badge>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- File d'attente --}}
        <x-ui.queue-list title="File d'attente" :items="$queue" class="lg:col-span-1" />

        {{-- Fiche patient --}}
        <div class="flex flex-col gap-6 lg:col-span-2">
            @if (!$patient)
                <x-ui.card class="flex flex-col items-center gap-2 py-12 text-center">
                    <x-ui.icon name="check-circle" class="h-8 w-8 text-success" />
                    <p class="font-medium text-ink">Aucun patient en attente</p>
                    <p class="text-sm text-ink-muted">La file de consultation est vide pour le moment.</p>
                </x-ui.card>
            @else
                <x-ui.card>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-4">
                            <x-ui.avatar :initials="$patient['initials']" size="lg" />
                            <div>
                                <h2 class="text-lg font-semibold text-ink">{{ $patient['name'] }}</h2>
                                <p class="font-mono text-xs text-ink-muted">{{ $patient['id'] }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <x-ui.badge tone="neutral">{{ $patient['age'] ?? '—' }} ans · {{ $patient['sex'] }}</x-ui.badge>
                                    <x-ui.badge tone="warning">Allergie&nbsp;: {{ $patient['allergy'] }}</x-ui.badge>
                                    <x-ui.badge tone="neutral">{{ $patient['coverage'] }}</x-ui.badge>
                                </div>
                                @if (!empty($patient['guardian_name']))
                                    <p class="mt-2 text-xs text-ink-muted">
                                        Parent/tuteur&nbsp;: <span class="font-medium text-ink-soft">{{ $patient['guardian_name'] }}</span>
                                        · Contact d'urgence&nbsp;: <span class="font-mono">{{ $patient['emergency_contact'] }}</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap justify-end gap-2">
                            {{-- Demander un examen de laboratoire --}}
                            <x-ui.slide-over title="Demander un examen">
                                <x-slot:trigger>
                                    <x-ui.button variant="secondary" size="sm">
                                        <x-ui.icon name="beaker" class="h-4 w-4" />
                                        Labo
                                    </x-ui.button>
                                </x-slot:trigger>

                                <form method="POST" action="{{ route('exams.store') }}" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="patient_id" value="{{ $patient['record_id'] }}">

                                    <p class="text-sm text-ink-muted">Patient&nbsp;: <span class="font-medium text-ink">{{ $patient['name'] }}</span> ({{ $patient['id'] }})</p>

                                    <div>
                                        <x-ui.field label="Examen demandé" name="name" placeholder="Ex : Bilan sanguin complet (NFS)" :value="old('name')" required autofocus />
                                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                                    </div>

                                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                        <x-ui.button type="submit" class="w-full justify-center">Envoyer au laboratoire</x-ui.button>
                                    </div>
                                </form>
                            </x-ui.slide-over>

                            {{-- Prescrire une ordonnance --}}
                            <x-ui.slide-over title="Nouvelle ordonnance">
                                <x-slot:trigger>
                                    <x-ui.button size="sm">
                                        <x-ui.icon name="document" class="h-4 w-4" />
                                        Ordonnance
                                    </x-ui.button>
                                </x-slot:trigger>

                                <form method="POST" action="{{ route('prescriptions.store') }}" class="flex flex-col gap-4">
                                    @csrf
                                    <input type="hidden" name="patient_id" value="{{ $patient['record_id'] }}">

                                    <p class="text-sm text-ink-muted">Patient&nbsp;: <span class="font-medium text-ink">{{ $patient['name'] }}</span> ({{ $patient['id'] }})</p>

                                    <div>
                                        <x-ui.field label="Date de l'ordonnance" name="date" type="date" :value="old('date', now()->format('Y-m-d'))" required />
                                        <x-input-error :messages="$errors->get('date')" class="mt-1" />
                                    </div>

                                    <div x-data="{ items: @js(old('items', [['medication_id' => '', 'quantity' => 1]])) }" class="flex flex-col gap-2">
                                        <label class="text-sm font-medium text-ink">Médicaments</label>

                                        <template x-for="(item, index) in items" :key="index">
                                            <div class="flex items-end gap-2">
                                                <div class="flex-1">
                                                    <select
                                                        :name="`items[${index}][medication_id]`"
                                                        x-model="item.medication_id"
                                                        class="w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                                    >
                                                        <option value="">Sélectionner un médicament</option>
                                                        @foreach ($medications as $medication)
                                                            <option value="{{ $medication->id }}">{{ $medication->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="w-20">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        :name="`items[${index}][quantity]`"
                                                        x-model="item.quantity"
                                                        placeholder="Qté"
                                                        class="w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                                                    >
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="items.length > 1 && items.splice(index, 1)"
                                                    class="shrink-0 rounded-md p-2 text-ink-muted hover:bg-surface-muted"
                                                >
                                                    <x-ui.icon name="x-circle" class="h-4 w-4" />
                                                </button>
                                            </div>
                                        </template>

                                        <button
                                            type="button"
                                            @click="items.push({ medication_id: '', quantity: 1 })"
                                            class="mt-1 flex items-center gap-1.5 self-start text-sm font-medium text-brand-600 hover:text-brand-700"
                                        >
                                            <x-ui.icon name="plus" class="h-4 w-4" />
                                            Ajouter un médicament
                                        </button>

                                        <x-input-error :messages="$errors->get('items')" class="mt-1" />
                                        <x-input-error :messages="$errors->get('items.*.medication_id')" class="mt-1" />
                                        <x-input-error :messages="$errors->get('items.*.quantity')" class="mt-1" />
                                    </div>

                                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                        <x-ui.button type="submit" class="w-full justify-center">Enregistrer l'ordonnance</x-ui.button>
                                    </div>
                                </form>
                            </x-ui.slide-over>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <form method="POST" action="{{ route('consultations.update', $consultation) }}" class="flex flex-col gap-4">
                        @csrf
                        <div>
                            <h3 class="text-sm font-semibold text-ink">Constantes</h3>
                            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                                @foreach ($patient['vitals'] as $vital)
                                    <div>
                                        <label class="text-[11px] uppercase tracking-wide text-ink-muted">{{ $vital['label'] }}</label>
                                        <input
                                            type="text"
                                            name="vitals[{{ $vital['label'] }}]"
                                            value="{{ old('vitals.'.$vital['label'], $vital['value']) }}"
                                            class="mt-1 w-full rounded-md border border-line bg-surface-sunken px-3 py-2 font-mono text-sm font-semibold text-ink focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-brand-500"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <x-ui.textarea label="Diagnostic & notes" name="notes" :rows="4" placeholder="Symptômes, diagnostic, recommandations...">{{ old('notes', $consultation->notes) }}</x-ui.textarea>
                        </div>

                        <div class="mt-2 flex justify-end border-t border-line pt-4">
                            <x-ui.button size="sm" type="submit">Enregistrer les constantes &amp; notes</x-ui.button>
                        </div>
                    </form>

                    <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-line pt-4">
                        {{-- Orienter vers un autre service --}}
                        <x-ui.slide-over title="Orienter vers un service">
                            <x-slot:trigger>
                                <x-ui.button variant="secondary" size="sm" type="button">
                                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                                    Orienter vers un service
                                </x-ui.button>
                            </x-slot:trigger>

                            <form method="POST" action="{{ route('referrals.store') }}" class="flex flex-col gap-4">
                                @csrf
                                <input type="hidden" name="patient_id" value="{{ $patient['record_id'] }}">

                                <p class="text-sm text-ink-muted">Patient&nbsp;: <span class="font-medium text-ink">{{ $patient['name'] }}</span> ({{ $patient['id'] }})</p>

                                <div>
                                    <x-ui.select label="Service" name="service" placeholder="Sélectionner un service" :options="$serviceOptions" required />
                                    <x-input-error :messages="$errors->get('service')" class="mt-1" />
                                </div>

                                <div>
                                    <x-ui.textarea label="Motif de l'orientation" name="reason" placeholder="Ex : Baisse d'acuité visuelle à évaluer" required></x-ui.textarea>
                                    <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                                </div>

                                <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                                    <x-ui.button type="submit" class="w-full justify-center">Confirmer l'orientation</x-ui.button>
                                </div>
                            </form>
                        </x-ui.slide-over>

                        <x-ui.button variant="danger" size="sm" :href="route('hospitalisation.index')">Hospitaliser</x-ui.button>

                        <form method="POST" action="{{ route('consultations.close', $consultation) }}">
                            @csrf
                            <x-ui.button size="sm" type="submit">Clôturer la consultation</x-ui.button>
                        </form>
                    </div>

                    @if ($patient['referrals']->isNotEmpty())
                        <div class="mt-4 flex flex-col gap-2 border-t border-line pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Orientations en cours</p>
                            @foreach ($patient['referrals'] as $referral)
                                <div class="flex items-center justify-between rounded-md border border-line bg-surface-sunken px-3 py-2">
                                    <div>
                                        <p class="text-sm font-medium text-ink">{{ $referral->service }}</p>
                                        <p class="text-xs text-ink-muted">{{ $referral->reason }}</p>
                                    </div>
                                    <x-ui.badge :tone="$referral->status_tone">{{ $referral->status }}</x-ui.badge>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>

                <x-ui.card>
                    <h3 class="mb-2 text-sm font-semibold text-ink">Parcours de la visite</h3>
                    @if (count($timeline) > 0)
                        <x-ui.timeline :steps="$timeline" />
                    @else
                        <p class="text-sm text-ink-muted">Aucun événement enregistré pour ce patient.</p>
                    @endif
                </x-ui.card>
            @endif
        </div>
    </div>

</x-layout.app-shell>
