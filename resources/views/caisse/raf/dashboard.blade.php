<x-layout.app-shell title="Supervision financière" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    @php
        $fmt = fn(int $amount) => \App\Support\ClinicMetrics::formatMoney($amount);
        $revenueTotals = array_column($monthlyRevenue ?? [], 'total');
        $revenueMax = max(1, ...$revenueTotals ?: [1]);
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Supervision financière</h1>
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

    {{-- KPIs financiers --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-5">
        <x-ui.stat-card label="Encaissé" :value="$fmt($finance['collected'])" icon="credit-card" />
        <x-ui.stat-card label="Attendu" :value="$fmt($finance['expected'])" icon="document" />
        <x-ui.stat-card label="Impayé" :value="$fmt($finance['pending'])" icon="x-circle" trend-tone="danger" />
        <x-ui.stat-card label="Panier moyen" :value="$fmt($finance['avg_basket'])" />
        <x-ui.stat-card label="Patients facturés" :value="$finance['paying_patients']" icon="users" />
    </div>

    {{-- CA par service + tendance --}}
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

    {{-- CA par catégorie --}}
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

    {{-- Accès rapide --}}
    <div class="mt-8">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">Accès rapide</h2>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="{{ route('caisse.sessions.index') }}"
                class="rounded-md border border-line bg-white px-3 py-2 text-xs font-medium text-ink-soft shadow-card transition hover:border-brand-500 hover:text-brand-700">
                Vacations
            </a>
            <a href="{{ route('caisse.index') }}"
                class="rounded-md border border-line bg-white px-3 py-2 text-xs font-medium text-ink-soft shadow-card transition hover:border-brand-500 hover:text-brand-700">
                Caisse
            </a>
        </div>
    </div>

</x-layout.app-shell>
