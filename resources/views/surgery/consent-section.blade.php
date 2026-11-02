{{--
    Pre-anesthetic consultation & consent signing section.
    Expected variable: $surgery (App\Models\Surgery)
    Include with: @include('surgery.consent-section', ['surgery' => $surgery])
--}}

@php
    $hasNotes = !empty($surgery->pre_anesthetic_notes);
    $hasSigned = $surgery->consent_signed_at !== null;
@endphp

<div class="space-y-6" x-data="{ editingNotes: false }">

    {{-- Pre-anesthetic consultation --}}
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">Consultation pré-anesthésique</h3>
            @if ($hasNotes)
                <x-ui.badge tone="success">Remplie</x-ui.badge>
            @else
                <x-ui.badge tone="warning">En attente</x-ui.badge>
            @endif
        </div>

        @if ($hasNotes && !$hasSigned)
            {{-- Notes filled but consent not yet signed: show read-only with edit toggle --}}
            <div x-show="!editingNotes">
                <div class="rounded-md border border-line bg-surface-sunken p-4 text-sm text-ink whitespace-pre-line">{{ $surgery->pre_anesthetic_notes }}</div>
                <button type="button"
                        @click="editingNotes = true"
                        class="mt-3 text-sm font-medium text-brand-700 hover:text-brand-800">
                    Modifier les notes
                </button>
            </div>
        @endif

        @if ($hasSigned && $hasNotes)
            {{-- Consent signed: notes are fully read-only --}}
            <div class="rounded-md border border-line bg-surface-sunken p-4 text-sm text-ink whitespace-pre-line">{{ $surgery->pre_anesthetic_notes }}</div>
        @endif

        @if (!$hasSigned)
            {{-- Form: shown by default when empty, or via edit toggle when filled --}}
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
                          placeholder="Saisir les observations de la consultation pré-anesthésique (min. 10 caractères)..."
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
    </x-ui.card>

    {{-- Consent signing --}}
    <x-ui.card>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-ink">Consentement chirurgical</h3>
            @if ($hasSigned)
                <x-ui.badge tone="success">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-3.5 w-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Signé
                </x-ui.badge>
            @else
                <x-ui.badge tone="{{ $hasNotes ? 'warning' : 'neutral' }}">Non signé</x-ui.badge>
            @endif
        </div>

        @if ($hasSigned)
            {{-- Read-only signed block --}}
            <div class="rounded-md border border-success/30 bg-success-tint p-4">
                <div class="flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-5 w-5 text-success">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium text-success">Consentement signé</span>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                    <dt class="text-ink-muted">Signé par</dt>
                    <dd class="text-ink font-medium">{{ $surgery->consent_signed_by }}</dd>
                    <dt class="text-ink-muted">Date</dt>
                    <dd class="text-ink">{{ $surgery->consent_signed_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        @else
            {{-- Consent form --}}
            <form method="POST" action="{{ route('surgery.consent', $surgery) }}">
                @csrf

                <label for="consent_signed_by_{{ $surgery->id }}" class="block text-sm font-medium text-ink mb-1">
                    Nom du patient ou tuteur signataire
                </label>
                <input type="text"
                       id="consent_signed_by_{{ $surgery->id }}"
                       name="consent_signed_by"
                       value="{{ old('consent_signed_by') }}"
                       maxlength="255"
                       required
                       placeholder="Nom complet du signataire"
                       @if (!$hasNotes) disabled @endif
                       class="w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink shadow-sm placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-surface-sunken disabled:opacity-60">

                @error('consent_signed_by')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror

                @if (!$hasNotes)
                    <p class="mt-2 text-xs text-ink-muted">
                        La consultation pré-anesthésique doit être remplie avant de pouvoir signer le consentement.
                    </p>
                @endif

                <div class="mt-3">
                    <button type="submit"
                            @if (!$hasNotes) disabled @endif
                            class="inline-flex items-center rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                        Signer le consentement
                    </button>
                </div>
            </form>
        @endif
    </x-ui.card>

</div>
