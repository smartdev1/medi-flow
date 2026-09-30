<?php

namespace App\Services;

use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Point d'entrée unique de tout encaissement.
 *
 * Deux règles sont garanties ici, quelle que soit l'origine de l'appel :
 *   1. aucun encaissement sans session de caisse ouverte — donc rattaché à un
 *      fonds déclaré, et par conséquent à un caissier identifiable ;
 *   2. aucun encaissement qui ne soit pas rapproché d'une facture.
 */
class CashierService
{
    /**
     * Session ouverte du caissier, ou échec explicite.
     */
    public function requireOpenSession(User $cashier): CashierSession
    {
        $session = CashierSession::where('cashier_id', $cashier->id)
            ->active()
            ->orderByDesc('opened_at')
            ->first();

        if (! $session) {
            throw new RuntimeException(
                'Vous devez ouvrir une session de caisse avant d\'encaisser un paiement.'
            );
        }

        return $session;
    }

    /**
     * Enregistre un encaissement manuel hors facture (laboratoire, pharmacie,
     * acte ponctuel saisi au guichet).
     */
    public function recordManualPayment(User $cashier, array $data): Payment
    {
        $session = $this->requireOpenSession($cashier);

        return DB::transaction(function () use ($cashier, $session, $data) {
            $payment = Payment::create([
                'patient_id' => $data['patient_id'],
                'visit_id' => $data['visit_id'] ?? null,
                'service' => $data['service'],
                'base_amount' => $data['base_amount'] ?? null,
                'taux' => $data['taux'] ?? null,
                'amount' => (int) $data['amount'],
                'method' => $data['method'],
                'status' => $data['status'] ?? Payment::STATUS_PAID,
                'cashier_id' => $cashier->id,
                'cashier_session_id' => $session->id,
            ]);

            return $payment;
        });
    }

    /**
     * Encaisse tout ou partie d'une facture.
     *
     * La caisse ne peut jamais encaisser plus que ce que le patient doit :
     * l'excédent est refusé, pas tronqué. Le refus est levé avant tout
     * encaissement et la transaction est annulée, donc aucun paiement orphelin
     * n'est laissé derrière.
     */
    public function settleInvoice(Invoice $invoice, int $amount, ?string $method, User $cashier): Payment
    {
        $session = $this->requireOpenSession($cashier);

        return DB::transaction(function () use ($invoice, $amount, $method, $cashier, $session) {
            $invoice->refresh();

            $payment = Payment::create([
                'patient_id' => $invoice->patient_id,
                'visit_id' => $invoice->visit_id,
                'invoice_id' => $invoice->id,
                'service' => $this->serviceLabel($invoice),
                'base_amount' => $invoice->subtotal,
                'taux' => $invoice->ticket_moderateur,
                'amount' => $amount,
                'method' => $method ?? Payment::METHOD_CASH,
                'status' => Payment::STATUS_PAID,
                'cashier_id' => $cashier->id,
                'cashier_session_id' => $session->id,
            ]);

            $invoice->settle($amount, $cashier, $method, $payment);

            return $payment;
        });
    }

    private function serviceLabel(Invoice $invoice): string
    {
        $visit = $invoice->visit;

        return $visit?->act?->label ?? 'Consultation';
    }
}
