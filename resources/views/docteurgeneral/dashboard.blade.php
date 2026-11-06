<x-layout.app-shell title="Supervision — Docteur Général" active="docteurgeneral" :user-name="$userName" :user-role="$userRole"
    :user-initials="$userInitials">

    @php
        $fmt = fn(int $amount) => \App\Support\ClinicMetrics::formatMoney($amount);
        $revenueTotals = array_column($monthlyRevenue ?? [], 'total');
        $revenueMax = max(1, ...$revenueTotals ?: [1]);
        $actsTotals = array_column($monthlyActs ?? [], 'total');
        $actsMax = max(1, ...$actsTotals ?: [1]);
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Supervision générale</h1>
            <p class="mt-1 text-sm text-ink-muted">Période&nbsp;: {{ $periodLabel }}.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Période</span>
            @foreach ($periods as $p)
                <a href="{{ request()->fullUrlWithQuery(['period' => $p['key']]) }}"
                    class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors {{ $period === $p['key'] ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-line text-ink-soft hover:border-brand-300 hover:text-brand-700' }}">
                    {{ $p['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Onglets --}}
    <div x-data="{ tab: 'synthese' }" class="mt-6">
        <div class="flex flex-wrap gap-1 border-b border-line">
            <button type="button" @click="tab = 'synthese'"
                :class="tab === 'synthese' ? 'border-brand-500 text-brand-700' :
                    'border-transparent text-ink-soft hover:text-ink'"
                class="border-b-2 px-4 py-2 text-sm font-medium transition-colors">Synthèse</button>
            <button type="button" @click="tab = 'finance'"
                :class="tab === 'finance' ? 'border-brand-500 text-brand-700' :
                    'border-transparent text-ink-soft hover:text-ink'"
                class="border-b-2 px-4 py-2 text-sm font-medium transition-colors">Finance</button>
            <button type="button" @click="tab = 'operations'"
                :class="tab === 'operations' ? 'border-brand-500 text-brand-700' :
                    'border-transparent text-ink-soft hover:text-ink'"
                class="border-b-2 px-4 py-2 text-sm font-medium transition-colors">Opérations</button>
        </div>

        {{-- ===================== SYNTHÈSE ===================== --}}
        <div x-show="tab === 'synthese'" class="pt-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
                <x-ui.stat-card label="CA (période)" :value="$fmt($finance['collected'])" icon="credit-card" />
                <x-ui.stat-card label="Taux d'occupation" :value="$operations['occupancy_rate'] . ' %'" icon="building" />
                <x-ui.stat-card label="Taux d'impayés" :value="$finance['unpaid_rate'] . ' %'" icon="x-circle" trendTone="danger" />
                <x-ui.stat-card label="Actes réalisés" :value="$operations['acts']['total']" icon="check-circle" />
                <x-ui.stat-card label="Temps d'attente" :value="$operations['avg_wait_minutes'] . ' min'" icon="clock" />
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
                <x-ui.card>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Tendance du CA (12 mois)
                    </h2>
                    <div class="mt-4 flex items-end gap-1.5 h-40">
                        @foreach ($monthlyRevenue as $point)
                            <div class="flex flex-1 flex-col items-center justify-end gap-1"
                                title="{{ $point['label'] }} : {{ $fmt($point['total']) }}">
                                <span class="text-[10px] text-ink-muted">{{ $fmt($point['total']) }}</span>
                                <div class="w-full rounded-t bg-brand-500"
                                    style="height: {{ round(($point['total'] / $revenueMax) * 100) }}%"></div>
                                <span class="text-[10px] text-ink-muted">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Tendance des actes (12
                        mois)</h2>
                    <div class="mt-4 flex items-end gap-1.5 h-40">
                        @foreach ($monthlyActs as $point)
                            <div class="flex flex-1 flex-col items-center justify-end gap-1"
                                title="{{ $point['label'] }} : {{ $point['total'] }} actes">
                                <span class="text-[10px] text-ink-muted">{{ $point['total'] }}</span>
                                <div class="w-full rounded-t bg-success"
                                    style="height: {{ round(($point['total'] / $actsMax) * 100) }}%"></div>
                                <span class="text-[10px] text-ink-muted">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>
        </div>

        {{-- ===================== FINANCE ===================== --}}
        <div x-show="tab === 'finance'" class="pt-6" x-cloak>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
                <x-ui.stat-card label="Encaissé" :value="$fmt($finance['collected'])" icon="credit-card" />
                <x-ui.stat-card label="Attendu" :value="$fmt($finance['expected'])" icon="document" />
                <x-ui.stat-card label="Impayé" :value="$fmt($finance['pending'])" icon="x-circle" trendTone="danger" />
                <x-ui.stat-card label="Taux recouvrement" :value="$finance['recovery_rate'] . ' %'" icon="check-circle" />
                <x-ui.stat-card label="Panier moyen" :value="$fmt($finance['avg_basket'])" />
                <x-ui.stat-card label="Patients facturés" :value="$finance['paying_patients']" icon="users" />
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-ui.card class="lg:col-span-2">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">CA par service</h2>
                    <div class="mt-4 flex flex-col gap-3">
                        @forelse ($finance['by_service'] as $service => $total)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-ink">{{ $service }}</span>
                                    <span class="font-medium text-ink">{{ $fmt($total) }}</span>
                                </div>
                                <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-surface-sunken">
                                    <div class="h-full rounded-full bg-brand-500"
                                        style="width: {{ $finance['collected'] > 0 ? round(($total / $finance['collected']) * 100) : 0 }}%">
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-ink-muted">Aucun encaissement sur la période.</p>
                        @endforelse
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Tendance du CA</h2>
                    <div class="mt-4 flex items-end gap-1.5 h-40">
                        @foreach ($monthlyRevenue as $point)
                            <div class="flex flex-1 flex-col items-center justify-end gap-1"
                                title="{{ $point['label'] }}">
                                <div class="w-full rounded-t bg-brand-500"
                                    style="height: {{ round(($point['total'] / $revenueMax) * 100) }}%"></div>
                                <span class="text-[10px] text-ink-muted">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                @php
                    $categories = [
                        ['label' => 'Consultation', 'key' => 'Consultation', 'color' => 'blue'],
                        ['label' => 'Laboratoire', 'key' => 'Laboratoire', 'color' => 'teal'],
                        ['label' => 'Pharmacie', 'key' => 'Pharmacie', 'color' => 'green'],
                        ['label' => 'Hospitalisation', 'key' => 'Hospitalisation', 'color' => 'purple'],
                        ['label' => 'Chirurgie', 'key' => 'Chirurgie', 'color' => 'orange'],
                        ['label' => 'Autre', 'key' => 'Autre', 'color' => 'gray'],
                    ];
                @endphp
                @foreach ($categories as $category)
                    <x-ui.card class="border border-line">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-ink-soft">{{ $category['label'] }}</p>
                            <span class="font-mono text-lg font-semibold text-ink">
                                {{ $fmt($finance['by_service_category'][$category['key']] ?? 0) }}
                            </span>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-surface-sunken">
                            @php
                                $total = $finance['by_service_category'][$category['key']] ?? 0;

                                $categoryTotals = array_values($finance['by_service_category'] ?? []);
                                $maxTotal = !empty($categoryTotals) ? max($categoryTotals) : 1;

                                $width = $maxTotal > 0 ? round(($total / $maxTotal) * 100) : 0;
                            @endphp
                            <div class="h-full rounded-full bg-brand-500" style="width: {{ $width }}%"></div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>

        {{-- ===================== OPÉRATIONS ===================== --}}
        <div x-show="tab === 'operations'" class="pt-6" x-cloak>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
                <x-ui.stat-card label="Occupation lits" :value="$operations['occupancy_rate'] . ' %'" icon="building" />
                <x-ui.stat-card label="Lits occupés" :value="$operations['bed_occupied'] . ' / ' . $operations['bed_capacity']" icon="bed" />
                <x-ui.stat-card label="Durée séjour (DMS)" :value="$operations['avg_stay_days'] . ' j'" icon="clock" />
                <x-ui.stat-card label="Attente moyenne" :value="$operations['avg_wait_minutes'] . ' min'" icon="clock" />
                <x-ui.stat-card label="Consultations" :value="$operations['acts']['consultations']" icon="stethoscope" />
                <x-ui.stat-card label="Hospitalisations" :value="$operations['acts']['admissions']" icon="building" />
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <x-ui.card class="lg:col-span-2">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Actes réalisés (période)
                    </h2>
                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="rounded-md border border-line bg-surface-sunken p-3 text-center">
                            <p class="text-2xl font-semibold text-ink">{{ $operations['acts']['consultations'] }}</p>
                            <p class="mt-1 text-xs text-ink-muted">Consultations</p>
                        </div>
                        <div class="rounded-md border border-line bg-surface-sunken p-3 text-center">
                            <p class="text-2xl font-semibold text-ink">{{ $operations['acts']['exams'] }}</p>
                            <p class="mt-1 text-xs text-ink-muted">Examens labo</p>
                        </div>
                        <div class="rounded-md border border-line bg-surface-sunken p-3 text-center">
                            <p class="text-2xl font-semibold text-ink">{{ $operations['acts']['prescriptions'] }}</p>
                            <p class="mt-1 text-xs text-ink-muted">Ordonnances</p>
                        </div>
                        <div class="rounded-md border border-line bg-surface-sunken p-3 text-center">
                            <p class="text-2xl font-semibold text-ink">{{ $operations['acts']['admissions'] }}</p>
                            <p class="mt-1 text-xs text-ink-muted">Admissions</p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card>
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Tendance des actes</h2>
                    <div class="mt-4 flex items-end gap-1.5 h-40">
                        @foreach ($monthlyActs as $point)
                            <div class="flex flex-1 flex-col items-center justify-end gap-1"
                                title="{{ $point['label'] }}">
                                <div class="w-full rounded-t bg-success"
                                    style="height: {{ round(($point['total'] / $actsMax) * 100) }}%"></div>
                                <span class="text-[10px] text-ink-muted">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <x-ui.card class="border-dashed border-line bg-surface-sunken">
                    <p class="text-xs uppercase tracking-wide text-ink-muted">Occupation des créneaux</p>
                    <p class="mt-2 text-lg font-semibold text-ink-muted">À connecter</p>
                    <p class="mt-1 text-xs text-ink-muted">Nécessite la planification des consultations.</p>
                </x-ui.card>
                <x-ui.card class="border-dashed border-line bg-surface-sunken">
                    <p class="text-xs uppercase tracking-wide text-ink-muted">Taux de no-show</p>
                    <p class="mt-2 text-lg font-semibold text-ink-muted">À connecter</p>
                    <p class="mt-1 text-xs text-ink-muted">Nécessite le suivi des annulations.</p>
                </x-ui.card>
                <x-ui.card class="border-dashed border-line bg-surface-sunken">
                    <p class="text-xs uppercase tracking-wide text-ink-muted">Utilisation équipements</p>
                    <p class="mt-2 text-lg font-semibold text-ink-muted">À connecter</p>
                    <p class="mt-1 text-xs text-ink-muted">Nécessite le suivi scanner / bloc.</p>
                </x-ui.card>
            </div>
        </div>
    </div>

    {{-- Accès rapide aux modules --}}
    <div class="mt-8">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Accès rapide</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ([['label' => 'Accueil', 'route' => 'accueil.index'], ['label' => "Salle d'attente", 'route' => 'attente.index'], ['label' => 'Médecin', 'route' => 'medecin.index'], ['label' => 'Laboratoire', 'route' => 'laboratoire.index'], ['label' => 'Pharmacie', 'route' => 'pharmacie.index'], ['label' => 'Caisse', 'route' => 'caisse.index'], ['label' => 'Logistique', 'route' => 'logistique.index'], ['label' => 'Hospitalisation', 'route' => 'hospitalisation.index'], ['label' => 'Sortie', 'route' => 'sortie.index'], ['label' => 'Chambres', 'route' => 'chambres.index']] as $module)
                <a href="{{ route($module['route']) }}"
                    class="rounded-md border border-line bg-white px-3 py-2 text-xs font-medium text-ink-soft shadow-card transition hover:border-brand-500 hover:text-brand-700">
                    {{ $module['label'] }}
                </a>
            @endforeach
        </div>
    </div>

</x-layout.app-shell>
