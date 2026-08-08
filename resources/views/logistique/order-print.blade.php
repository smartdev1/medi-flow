<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Commande {{ $order->reference }} — Clinique Saint Matthieu</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-white p-10 font-sans text-ink">
    <div class="mx-auto max-w-2xl">
        <div class="flex items-start justify-between border-b border-line pb-6">
            <div>
                <h1 class="text-xl font-semibold text-ink">Clinique Saint Matthieu</h1>
                <p class="text-sm text-ink-muted">Bon de commande — Pharmacie</p>
            </div>
            <div class="text-right">
                <p class="font-mono text-lg font-semibold text-ink">{{ $order->reference }}</p>
                <p class="text-sm text-ink-muted">{{ $order->ordered_at->format('d/m/Y') }}</p>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Demandée par</p>
                <p class="mt-1 text-ink">{{ $order->requestedBy?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Statut</p>
                <p class="mt-1 text-ink">{{ $order->status }}</p>
            </div>
        </div>

        <table class="mt-8 w-full text-left text-sm">
            <thead>
                <tr class="border-b border-line text-xs uppercase tracking-wide text-ink-muted">
                    <th class="py-2 font-semibold">Médicament</th>
                    <th class="py-2 font-semibold">Unité</th>
                    <th class="py-2 text-right font-semibold">Quantité commandée</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="py-2.5">{{ $item->medication->name }}</td>
                        <td class="py-2.5 text-ink-soft">{{ $item->medication->unit }}</td>
                        <td class="py-2.5 text-right font-mono">{{ $item->quantity }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($order->notes)
            <div class="mt-6 border-t border-line pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-muted">Notes</p>
                <p class="mt-1 text-sm text-ink-soft">{{ $order->notes }}</p>
            </div>
        @endif

        <div class="mt-10 flex justify-end print:hidden">
            <button onclick="window.print()" class="rounded-md border border-line bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-surface-muted">
                Imprimer ce document
            </button>
        </div>
    </div>
</body>
</html>
