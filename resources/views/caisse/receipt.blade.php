<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reçu {{ $payment->receipt_number }} — {{ $clinicName }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page {
            size: A5 portrait;
            margin: 12mm;
        }

        html, body {
            background-color: #f5f7fa;
            color: #1f2937;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            padding: 0;
        }

        .receipt {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 32px 72px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .receipt-inner {
            padding: 32px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: flex-start;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            font-size: 0.95rem;
            color: #111827;
        }

        .brand-badge {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2563eb 0%, #4f46e5 100%);
            color: #ffffff;
            display: grid;
            place-items: center;
            font-size: 0.95rem;
            font-weight: 800;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.18);
        }

        .meta {
            text-align: right;
        }

        .meta .label {
            display: block;
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.16em;
            margin-bottom: 6px;
        }

        .meta .value {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .meta .subvalue {
            font-size: 0.88rem;
            color: #6b7280;
        }

        .grid-two {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-top: 32px;
        }

        .box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 20px;
        }

        .box-title {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            margin-bottom: 12px;
        }

        .box p {
            margin: 0 0 10px;
            font-size: 0.95rem;
            color: #334155;
        }

        .box p span {
            color: #111827;
            font-weight: 600;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 32px;
        }

        .table thead tr {
            background: #f8fafc;
        }

        .table th,
        .table td {
            padding: 16px 18px;
            font-size: 0.92rem;
        }

        .table th {
            text-align: left;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .table td {
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .total-row td {
            border-top: 2px solid #e5e7eb;
            font-weight: 700;
            color: #111827;
        }

        .total-row .label {
            text-align: right;
            padding-right: 18px;
            color: #475569;
            font-size: 0.95rem;
        }

        .total-row .amount {
            text-align: right;
            font-size: 1rem;
            letter-spacing: 0.02em;
        }

        .note {
            margin-top: 32px;
            display: flex;
            justify-content: space-between;
            gap: 24px;
            align-items: center;
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.7;
        }

        .signatures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-top: 32px;
        }

        .signature {
            border-top: 1px dashed #cbd5e1;
            padding-top: 14px;
            color: #475569;
            font-size: 0.84rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
        }

        .print-hidden {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-top: 32px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 10px 18px;
            font-size: 0.88rem;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #111827;
            text-decoration: none;
        }

        .button.primary {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        @media print {
            body {
                background: #ffffff;
            }
            .receipt {
                box-shadow: none;
                border: none;
                margin: 0;
                width: 100%;
            }
            .print-hidden {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-inner">
            <div class="header">
                <div>
                    <div class="brand">
                        <span class="brand-badge">R</span>
                        <span>{{ $clinicName }}</span>
                    </div>
                    <p style="margin-top: 10px; font-size: 0.95rem; color: #475569; max-width: 420px;">
                        Reçu de paiement officiel, valide pour toutes les transactions réalisées en caisse.
                    </p>
                </div>
                <div class="meta">
                    <span class="label">Reçu</span>
                    <span class="value">{{ $payment->receipt_number }}</span>
                    <span class="subvalue">{{ $payment->created_at->format('d/m/Y') }} • {{ $payment->created_at->format('H:i') }}</span>
                </div>
            </div>

            <div class="grid-two">
                <div class="box">
                    <div class="box-title">Patient</div>
                    <p><span>{{ $payment->patient->full_name }}</span></p>
                    <p>Code patient : <span>{{ $payment->patient->code }}</span></p>
                    @if ($payment->patient->sex)
                        <p>Sexe : <span>{{ $payment->patient->sex === 'M' ? 'Masculin' : ($payment->patient->sex === 'F' ? 'Féminin' : $payment->patient->sex) }}</span></p>
                    @endif
                    @if ($payment->patient->birth_date)
                        <p>Né(e) le : <span>{{ $payment->patient->birth_date->format('d/m/Y') }}</span></p>
                    @endif
                    @if ($payment->patient->phone)
                        <p>Tél : <span>{{ $payment->patient->phone }}</span></p>
                    @endif
                    @if ($payment->patient->coverage)
                        <p>Couverture : <span>{{ $payment->patient->coverage }}</span></p>
                    @endif
                </div>
                <div class="box">
                    <div class="box-title">Détails du paiement</div>
                    <p>Statut : <span>{{ $payment->status }}</span></p>
                    <p>Mode : <span>{{ $payment->method }}</span></p>
                    <p>Service : <span>{{ $payment->service }}</span></p>
                    @if ($payment->visit)
                        <p>Visite : <span>#{{ $payment->visit->id }}</span></p>
                    @endif
                    <p>Encaissé par : <span>{{ $payment->cashier?->name ?? '—' }}</span></p>
                    @if ($payment->closed_at)
                        <p>Caisse clôturée : <span>{{ $payment->closed_at->format('d/m/Y H:i') }}</span></p>
                    @endif
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th style="text-align: right;">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $payment->service }}</td>
                        <td style="text-align: right; font-feature-settings: 'tnum';">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td class="label">Total</td>
                        <td class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</td>
                    </tr>
                </tfoot>
            </table>

            <div class="signatures">
                <div class="signature">Signature patient</div>
                <div class="signature">Signature caissier</div>
            </div>

            <div class="note">
                <div>
                    Reçu généré le {{ now()->format('d/m/Y à H:i') }}.
                </div>
                <div>
                    Merci pour votre confiance.
                </div>
            </div>

            <div class="print-hidden">
                <a href="{{ route('caisse.index') }}" class="button">← Retour à la caisse</a>
                <button type="button" onclick="window.print()" class="button primary">Imprimer</button>
            </div>
        </div>
    </div>
</body>
</html>
