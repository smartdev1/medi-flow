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
                <form method="POST" action="{{ route('caisse.paniers.pay', $invoice) }}">
                    @csrf
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
                                ({{ $invoice->patient->insuranceCompany->rate }}%)
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
