<?php

namespace App\Services;

use App\Exceptions\PaymentRefusedException;
use App\Models\CashierSession;
use App\Models\Invoice;
use App\Models\Medication;
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
     * Vacation désignée du point de vente pharmacie, ou null si aucune n'est
     * ouverte.
     *
     * Le point de vente est unique pour l'établissement : contrairement à la
     * caisse, où chaque caissier a sa vacation, c'est la dernière vacation
     * ouverte qui fait foi. Deux point de vente actifs signifieraient deux
     * fonds distincts que personne ne rapproche. Si une anomalie en a créé un
     * second, le plus récent est conservé et les autres sont fermés plutôt que
     * laissés ouverts en concurrence.
     */
    public function openCashPointSession(): ?CashierSession
    {
        $sessions = CashierSession::cashPoint()->active()->orderByDesc('opened_at')->get();

        $designated = $sessions->first();

        foreach ($sessions->skip(1) as $stale) {
            $stale->update(['closed_at' => now()]);
        }

        return $designated;
    }

    /**
     * Vacation de caisse du caissier, ou échec explicite.
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
     * Enregistre une vente de médicaments au point de vente pharmacie.
     *
     * Contrairement à la caisse, la vente n'exige aucune vacation : un patient
     * peut se présenter sans passer par la consultation, et exiger de lui une
     * session de caisse bloquerait la vente pour une raison sans rapport. Le
     * paiement est donc rattaché à la vacation désignée du point de vente
     * lorsqu'il y en a une ouverte, et à aucune sinon.
     *
     * Le prix vient du catalogue `medications`, jamais de la requête : le
     * navigateur ne peut pas proposer un tarif. La vente n'emporte aucun ticket
     * modérateur.
     */
    public function recordPharmacySale(User $pharmacist, array $data): Payment
    {
        $items = $data['items'] ?? [];

        if ($items === []) {
            throw new PaymentRefusedException('La vente doit porter au moins un médicament.');
        }

        $session = $this->openCashPointSession();

        return DB::transaction(function () use ($pharmacist, $data, $items, $session) {
            $total = 0;

            foreach ($items as $item) {
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($quantity <= 0) {
                    throw new PaymentRefusedException('La quantité vendue doit être supérieure à zéro.');
                }

                $medication = Medication::query()->lockForUpdate()->find($item['medication_id'] ?? null);

                if (! $medication) {
                    throw new PaymentRefusedException('Un médicament vendu est introuvable au catalogue.');
                }

                if ($medication->stock_quantity < $quantity) {
                    throw new PaymentRefusedException(
                        "Stock insuffisant pour {$medication->name} : {$medication->stock_quantity} restant(s)."
                    );
                }

                $medication->decrement('stock_quantity', $quantity);

                $total += $quantity * (int) $medication->unit_price;
            }

            return Payment::create([
                'patient_id' => $data['patient_id'],
                'visit_id' => $data['visit_id'] ?? null,
                'service' => $data['service'] ?? 'Vente pharmacie',
                'base_amount' => $total,
                // La colonne porte `NOT NULL DEFAULT 0` : « pas de ticket
                // modérateur » s'y inscrit donc par 0, non par null.
                'taux' => 0,
                'amount' => $total,
                'method' => $data['method'] ?? Payment::METHOD_CASH,
                'status' => Payment::STATUS_PAID,
                'cashier_id' => $pharmacist->id,
                'cashier_session_id' => $session?->id,
            ]);
        });
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
