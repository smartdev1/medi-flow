<x-layout.app-shell title="Panier #{{ $invoice->id }}" active="caisse" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Panier #{{ $invoice->id }}</h1>
            <p class="mt-1 text-sm text-ink-muted">
                Visite du {{ $invoice->visit->arrived_at->format('d/m/Y') }} — {{ $invoice->patient->full_name }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('caisse.index') }}" class="rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-soft hover:bg-surface-sunken">
                Retour
            </a>
            @if ($invoice->isAwaitingPayment() || $invoice->isPending())
                <form method="POST" action="{{ route('caisse.paniers.pay', $invoice) }}" class="flex flex-wrap items-end justify-end gap-2">
                    @csrf
                    <div>
                        <label for="amount" class="text-xs font-medium text-ink-soft">Montant</label>
                        <input type="number"
                               name="amount"
                               id="amount"
                               min="1"
                               max="{{ $invoice->remaining_amount }}"
                               value="{{ old('amount', $invoice->remaining_amount) }}"
                               class="mt-1 w-32 rounded-md border border-line bg-white px-2 py-2 text-right font-mono text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>
                    <div>
                        <label for="method" class="text-xs font-medium text-ink-soft">Mode de paiement</label>
                        <select name="method"
                                id="method"
                                class="mt-1 rounded-md border border-line bg-white px-2 py-2 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @foreach (array_keys(config('clinic.payment_methods')) as $method)
                                <option value="{{ $method }}" @selected(old('method', $method) === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="rounded-md bg-success px-4 py-2 text-sm font-medium text-white hover:bg-success/90">
                        Encaisser
                    </button>
                </form>
            @endif
            @if ($invoice->isPaid())
                @php
                    $settlementPaymentId = $invoice->visit->payments->firstWhere('status', 'Payé')?->id;
                @endphp
                @if ($settlementPaymentId)
                    <a href="{{ route('payments.receipt', $settlementPaymentId) }}" target="_blank" class="rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                        Reçu
                    </a>
                @endif
            @endif
        </div>
    </div>

    @if ($invoice->isAwaitingPayment() || $invoice->isPending())
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-md border px-4 py-3 text-sm {{ $cashierSession ? 'border-success/20 bg-success-tint text-success' : 'border-warning/20 bg-warning-tint text-warning' }}">
            <div class="flex items-center gap-2">
                <x-ui.icon :name="$cashierSession ? 'check-circle' : 'alert-triangle'" class="h-4 w-4 shrink-0" />
                @if ($cashierSession)
                    <span>Session de caisse ouverte depuis {{ $cashierSession->opened_at->format('d/m/Y H:i') }} — l'encaissement sera rattaché à ce fonds.</span>
                @else
                    <span>Aucune session de caisse ouverte : l'encaissement sera refusé tant qu'une session n'est pas ouverte.</span>
                @endif
            </div>
            @unless ($cashierSession)
                <a href="{{ route('caisse.sessions.open') }}" class="rounded-md border border-warning/30 bg-white px-3 py-1.5 text-xs font-medium text-warning hover:bg-warning-tint">
                    Ouvrir une session
                </a>
            @endunless
        </div>
    @endif

    {{-- Surgery cost adjustment --}}
    @if ($invoice->visit->surgeries->where('status', \App\Models\Surgery::STATUS_COMPLETED)->isNotEmpty() && ($invoice->isAwaitingPayment() || $invoice->isPending()))
        <div class="mt-4">
            <x-ui.card>
                <h2 class="mb-3 text-sm font-semibold text-ink">Frais chirurgicaux</h2>
                @foreach ($invoice->visit->surgeries->where('status', \App\Models\Surgery::STATUS_COMPLETED) as $surgery)
                    <form method="POST" action="{{ route('surgery.update-cost', $surgery) }}" class="flex flex-wrap items-end gap-3 @if(!$loop->last) mb-3 pb-3 border-b border-line @endif">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1 min-w-[200px]">
                            <p class="text-xs text-ink-muted">{{ $surgery->procedure_name }}</p>
                        </div>
                        <div>
                            <label for="cost_{{ $surgery->id }}" class="text-xs font-medium text-ink-soft">Montant (FCFA)</label>
                            <input type="number" name="cost" id="cost_{{ $surgery->id }}" min="0" step="1"
                                   value="{{ old('cost', $surgery->cost) }}"
                                   class="mt-1 w-36 rounded-md border border-line bg-white px-2 py-2 text-right font-mono text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        </div>
                        <button type="submit" class="rounded-md border border-brand-500 px-3 py-2 text-xs font-medium text-brand-600 hover:bg-brand-50">
                            Modifier
                        </button>
                    </form>
                @endforeach
            </x-ui.card>
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-ink">Déroulé de la facturation</h2>
                <div class="flex flex-col gap-0">
                    @foreach ($timeline as $index => $step)
                        @php
                            $isLast = $loop->last;
                            $time = \Carbon\Carbon::parse($step['time'])->format('d/m/Y H:i');
                        @endphp
                        <div class="flex gap-4 @if(!$isLast) border-b border-line pb-4 @endif">
                            <div class="flex flex-col items-center">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-brand-500 bg-brand-500 text-white">
                                    <x-ui.icon :name="$step['icon'] ?? 'clock'" class="h-4 w-4" />
                                </span>
                                @if(!$isLast)
                                    <span class="mt-1 h-full w-px bg-line"></span>
                                @endif
                            </div>
                            <div class="flex flex-1 flex-col gap-1">
                                <div class="flex flex-wrap items-center justify-between gap-x-3">
                                    <p class="text-sm font-semibold text-ink">{{ $step['label'] }}</p>
                                    <span class="font-mono text-xs text-ink-muted">{{ $time }}</span>
                                </div>
                                <p class="text-xs text-ink-muted">{{ $step['meta'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium text-ink">{{ number_format($step['amount'], 0, ',', ' ') }} FCFA</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        </div>

        <div>
            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-ink">Résumé</h2>
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-ink-muted">Patient</p>
                        <p class="text-sm font-medium text-ink">{{ $invoice->patient->full_name }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-ink-muted">Couverture</p>
                        <p class="text-sm font-medium text-ink">
                            {{ $invoice->patient->insuranceCompany?->name ?? 'Aucune' }}
                            @if ($invoice->patient->insuranceCompany)
                                (ticket modérateur {{ rtrim(rtrim((string) $invoice->ticket_moderateur, '0'), '.') ?: '0' }} %)
                            @endif
                        </p>
                    </div>
                    <div class="border-t border-line pt-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-ink-muted">Sous-total</p>
                            <p class="text-sm font-medium text-ink">{{ $invoice->formatted_subtotal }}</p>
                        </div>
                        <div class="mt-2 flex items-center justify-between">
                            <p class="text-sm text-ink-muted">Part assurance</p>
                            <p class="text-sm font-medium text-success">-{{ $invoice->formatted_insurance_amount }}</p>
                        </div>
                        <div class="mt-2 flex items-center justify-between border-t border-line pt-2">
                            <p class="text-sm font-semibold text-ink">À payer</p>
                            <p class="text-lg font-bold text-ink">{{ $invoice->formatted_patient_amount }}</p>
                        </div>
                    </div>
                    @if ($invoice->isPaid())
                        <div class="border-t border-line pt-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-ink-muted">Payé</p>
                                <p class="text-sm font-medium text-success">{{ $invoice->formatted_paid_amount }}</p>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <p class="text-sm text-ink-muted">Caissier</p>
                                <p class="text-sm text-ink">{{ $invoice->cashier?->name ?? '—' }}</p>
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <p class="text-sm text-ink-muted">Date</p>
                                <p class="text-sm text-ink">{{ $invoice->paid_at?->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </x-ui.card>
        </div>
    </div>

</x-layout.app-shell>
