{{--
    Pre-anesthetic consultation section (optional).
    Expected variable: $surgery (App\Models\Surgery)
--}}

@php
    $hasNotes = !empty($surgery->pre_anesthetic_notes);
    $canEdit = in_array($surgery->status, [
        \App\Models\Surgery::STATUS_SCHEDULED,
    ]);
@endphp

<div class="space-y-4" x-data="{ editingNotes: false }">

    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">Notes pré-anesthésiques</h3>
            @if ($hasNotes)
                <x-ui.badge tone="success">Remplies</x-ui.badge>
            @else
                <x-ui.badge tone="neutral">Non remplies</x-ui.badge>
            @endif
        </div>

        @if ($hasNotes && $canEdit)
            <div x-show="!editingNotes">
                <div class="rounded-md border border-line bg-surface-sunken p-4 text-sm text-ink whitespace-pre-line">{{ $surgery->pre_anesthetic_notes }}</div>
                <button type="button"
                        @click="editingNotes = true"
                        class="mt-3 text-sm font-medium text-brand-700 hover:text-brand-800">
                    Modifier
                </button>
            </div>
        @endif

        @if ($hasNotes && !$canEdit)
            <div class="rounded-md border border-line bg-surface-sunken p-4 text-sm text-ink whitespace-pre-line">{{ $surgery->pre_anesthetic_notes }}</div>
        @endif

        @if ($canEdit)
            <form method="POST" action="{{ route('surgery.pre-anesthetic', $surgery) }}"
                  @if ($hasNotes) x-show="editingNotes" x-cloak @endif>
                @csrf
                @method('PATCH')

                <label for="pre_anesthetic_notes_{{ $surgery->id }}" class="block text-sm font-medium text-ink mb-1">
                    Notes pré-anesthésiques
                </label>
                <textarea id="pre_anesthetic_notes_{{ $surgery->id }}"
                          name="pre_anesthetic_notes"
                          rows="5"
                          required
                          minlength="10"
                          placeholder="Saisir les observations (min. 10 caractères)..."
                          class="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink shadow-sm placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
                >{{ old('pre_anesthetic_notes', $surgery->pre_anesthetic_notes) }}</textarea>

                @error('pre_anesthetic_notes')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror

                <div class="mt-3 flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2">
                        Enregistrer
                    </button>
                    @if ($hasNotes)
                        <button type="button" @click="editingNotes = false"
                                class="text-sm text-ink-muted hover:text-ink">
                            Annuler
                        </button>
                    @endif
                </div>
            </form>
        @endif

        @if ($surgery->consent_signed_at)
            <div class="mt-4 rounded-md border border-success/30 bg-success-tint p-3">
                <p class="text-xs text-success font-medium">Consentement enregistré le {{ $surgery->consent_signed_at->format('d/m/Y H:i') }} par {{ $surgery->consent_signed_by }}</p>
            </div>
        @endif
    </x-ui.card>

</div>
