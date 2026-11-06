{{--
    Operative Report (Compte-rendu opératoire) component.
    Expected variable: $surgery (App\Models\Surgery)
    Include with: @include("surgery.operative-report.index", ["surgery" => $surgery])
--}}

@php
    $hasReport = !empty($surgery->operative_report);
    $reportBy = $surgery->operativeReportBy?->name;
    $reportAt = $surgery->operative_report_at;
@endphp

<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-ink">Compte-rendu opératoire</h3>
        @if ($hasReport)
            <x-ui.badge tone="success">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Rédigé
            </x-ui.badge>
        @else
            <x-ui.badge tone="warning">En attente</x-ui.badge>
        @endif
    </div>

    @if ($hasReport)
        {{-- Read-only view --}}
        <div class="space-y-4">
            <div class="rounded-md border border-line bg-surface-sunken p-4 text-sm text-ink whitespace-pre-line">
                {{ $surgery->operative_report }}
            </div>

            <div class="pt-4 border-t border-line text-xs text-ink-muted flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Rédigé par {{ $reportBy }} le {{ $reportAt->format("d/m/Y H:i") }}</span>
            </div>

            <div class="flex justify-end">
                <a href="{{ route("surgery.operative-report.pdf", $surgery) }}"
                   class="inline-flex items-center gap-1.5 rounded-md border border-line bg-white px-4 py-2 text-sm font-medium text-ink-soft hover:bg-surface-muted">
                    <x-ui.icon name="printer" class="h-4 w-4" />
                    Imprimer / PDF
                </a>
            </div>
        </div>

    @else
        {{-- Form to submit --}}
        <form method="POST" action="{{ route("surgery.operative-report", $surgery) }}" class="space-y-4">
            @csrf

            <label for="operative_report" class="block text-sm font-medium text-ink mb-1">
                Compte-rendu opératoire (min. 50 caractères)
            </label>
            <textarea id="operative_report"
                      name="operative_report"
                      rows="10"
                      required
                      minlength="50"
                      placeholder="Décrivez l'intervention, les constatations, les suites opératoires prévues..."
                      class="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">{{ old("operative_report") }}</textarea>

            @error("operative_report")
                <p class="text-xs text-danger">{{ $message }}</p>
            @enderror

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Enregistrer le compte-rendu
                </button>
            </div>
        </form>
    @endif
</x-ui.card>
