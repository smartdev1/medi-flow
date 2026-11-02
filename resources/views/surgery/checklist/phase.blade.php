{{--
    Single phase of WHO checklist.
    Expected: $phase (string), $title (string), $items (array), $checklist (SurgeryChecklist|null), $surgery (Surgery)
--}}

@php
    $isCompleted = $checklist !== null;
    $submittedItems = $checklist?->items ?? [];
    $submittedNotes = $checklist?->notes;
    $checkedBy = $checklist?->checkedBy?->name;
    $checkedAt = $checklist?->checked_at;
@endphp

<x-ui.card>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
        @if ($isCompleted)
            <x-ui.badge tone="success">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Complétée
            </x-ui.badge>
        @else
            <x-ui.badge tone="warning">En attente</x-ui.badge>
        @endif
    </div>

    @if ($isCompleted)
        {{-- Read-only view --}}
        <div class="space-y-3">
            @foreach ($items as $key => $label)
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           {{ $submittedItems[$key] ?? false ? "checked" : "" }}
                           disabled
                           class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500">
                    <label class="text-sm text-ink">{{ $label }}</label>
                </div>
            @endforeach

            @if (!empty($submittedNotes))
                <div class="mt-4 p-3 rounded-md border border-line bg-surface-sunken">
                    <p class="text-xs font-medium text-ink-muted">Notes</p>
                    <p class="text-sm text-ink whitespace-pre-line">{{ $submittedNotes }}</p>
                </div>
            @endif

            <div class="mt-4 pt-4 border-t border-line text-xs text-ink-muted flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>Validé par {{ $checkedBy }} le {{ $checkedAt->format("d/m/Y H:i") }}</span>
            </div>
        </div>

    @else
        {{-- Form to submit --}}
        <form method="POST" action="{{ route("surgery.checklist", ["surgery" => $surgery, "phase" => $phase]) }}" class="space-y-3">
            @csrf

            @foreach ($items as $key => $label)
                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           name="items[{{ $key }}]"
                           value="1"
                           class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-500">
                    <label class="text-sm text-ink cursor-pointer">{{ $label }}</label>
                </div>
            @endforeach

            <div class="mt-4">
                <label for="notes_{{ $phase }}" class="block text-sm font-medium text-ink mb-1">Notes (optionnel)</label>
                <textarea id="notes_{{ $phase }}"
                          name="notes"
                          rows="3"
                          placeholder="Observations complémentaires..."
                          class="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">{{ old("notes") }}</textarea>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                    Valider {{ ucfirst(str_replace("_", " ", $phase)) }}
                </button>
            </div>
        </form>
    @endif
</x-ui.card>
