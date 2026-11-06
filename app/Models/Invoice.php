<?php

namespace App\Models;

use App\Enums\ActCategory;
use App\Exceptions\PaymentRefusedException;
use App\Models\Act;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_PARTIAL = 'partially_paid';
    public const STATUS_PAID = 'paid';

    protected $fillable = [
        'visit_id',
        'patient_id',
        'status',
        'subtotal',
        'insurance_amount',
        'patient_amount',
        'paid_amount',
        'cashier_id',
        'paid_at',
        'printed_at',
        'ticket_moderateur',
        'base_amount',
        'insurance_name',
        'breakdown',
        'agreed_stay_amount',
    ];

    protected $casts = [
        'subtotal' => 'integer',
        'insurance_amount' => 'integer',
        'patient_amount' => 'integer',
        'paid_amount' => 'integer',
        'paid_at' => 'datetime',
        'printed_at' => 'datetime',
        'ticket_moderateur' => 'decimal:2',
        'base_amount' => 'integer',
        'agreed_stay_amount' => 'integer',
        'breakdown' => 'array',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAwaitingPayment($query)
    {
        return $query->whereIn('status', [self::STATUS_AWAITING_PAYMENT, self::STATUS_PARTIAL]);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', '!=', self::STATUS_PAID);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Factures dont l'activité tombe aujourd'hui : créées aujourd'hui, ou
     * modifiées aujourd'hui parce qu'une ligne a été ajoutée au dossier.
     * Sans ce second critère, la facturation du jour reste invisible de la
     * caisse du jour.
     */
    public function scopeActiveOn($query, $date)
    {
        return $query->where(function ($q) use ($date) {
            $q->whereDate('created_at', $date)
                ->orWhereDate('updated_at', $date);
        });
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPartial(): bool
    {
        return $this->status === self::STATUS_PARTIAL;
    }

    public function isAwaitingPayment(): bool
    {
        return in_array($this->status, [self::STATUS_AWAITING_PAYMENT, self::STATUS_PARTIAL], true);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return number_format($this->subtotal, 0, ',', ' ').' FCFA';
    }

    public function getFormattedInsuranceAmountAttribute(): string
    {
        return number_format($this->insurance_amount, 0, ',', ' ').' FCFA';
    }

    public function getFormattedPatientAmountAttribute(): string
    {
        return number_format($this->patient_amount, 0, ',', ' ').' FCFA';
    }

    public function getFormattedPaidAmountAttribute(): string
    {
        return number_format($this->paid_amount, 0, ',', ' ').' FCFA';
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, $this->patient_amount - $this->paid_amount);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_PARTIAL => 'info',
            self::STATUS_AWAITING_PAYMENT => 'warning',
            default => 'neutral',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'Payé',
            self::STATUS_PARTIAL => 'Partiellement payé',
            self::STATUS_AWAITING_PAYMENT => 'À payer',
            default => 'En cours',
        };
    }

    /**
     * Récupère — ou crée — la facture d'une visite et la recalcule.
     *
     * Point d'entrée unique de la facturation : toute création de ligne
     * payable (acte, examen, ordonnance, journée d'hospitalisation) passe par
     * ici, ce qui garantit qu'aucun service consommé n'échappe à la facture du
     * dossier.
     *
     * Le ticket modérateur est celui de la venue, saisi par l'accueil : il est
     * propre à chaque consultation. Sans cela le taux resterait nul et le tiers
     * payant ne serait jamais déduit : le patient paierait la totalité, en
     * silence. Une facture déjà ajustée par la caisse conserve son taux.
     */
    public static function forVisit(Visit $visit): self
    {
        $invoice = static::firstOrCreate(
            ['visit_id' => $visit->id],
            [
                'patient_id' => $visit->patient_id,
                'status' => self::STATUS_PENDING,
                'ticket_moderateur' => $visit->ticket_moderateur
                    ?? config('clinic.default_ticket_rate', 20),
            ]
        );

        $invoice->recalculate();

        return $invoice;
    }

    public function recalculate(): void
    {
        $visit = $this->visit->fresh() ?? $this->visit;
        $patient = $this->patient->fresh() ?? $this->patient;

        if (! $visit->relationLoaded('surgeries')) {
            $visit->load('surgeries');
        }

        $surgeryCost = $visit->surgeries
            ->where('status', '!=', Surgery::STATUS_CANCELLED)
            ->sum('cost');

        // Surgery cost is all-inclusive: consultation, hospitalization,
        // anesthesia, medication, room — everything is in the global amount.
        if ($surgeryCost > 0) {
            $breakdown = [ActCategory::Chirurgie->value => $surgeryCost];
            $isInsured = (bool) $patient->insurance_company_id;

            if ($isInsured) {
                $rate = ((float) $this->ticket_moderateur) / 100;
                $ticket = (int) round($surgeryCost * $rate);
                $insuranceAmount = $surgeryCost - $ticket;
                $patientAmount = $ticket;
            } else {
                $patientAmount = $surgeryCost;
                $insuranceAmount = 0;
            }

            $this->update([
                'subtotal' => $surgeryCost,
                'insurance_amount' => $insuranceAmount,
                'patient_amount' => $patientAmount,
                'breakdown' => $breakdown,
            ]);

            return;
        }

        // Non-surgery visit: standard billing (act + exams + pharmacy + stay).
        if (! $visit->relationLoaded('act')) {
            $visit->load('act');
        }

        $actsTotal = 0;
        $breakdown = [];

        if ($visit->act) {
            $price = $patient->insurance_company_id && $visit->act->insured_price
                ? $visit->act->insured_price
                : $visit->act->base_price;
            $actsTotal += $price;

            if ($price > 0) {
                $category = $visit->act->category instanceof ActCategory
                    ? $visit->act->category
                    : ActCategory::Consultation;
                $breakdown[$category->value] = ($breakdown[$category->value] ?? 0) + $price;
            }
        }

        $visitExams = $patient->exams->filter(
            fn (Exam $exam) => $exam->visit_id === null || $exam->visit_id === $visit->id
        );

        $examsTotal = $visitExams->sum('price');
        $actsTotal += $examsTotal;

        if ($examsTotal > 0) {
            $breakdown[ActCategory::Laboratoire->value] = ($breakdown[ActCategory::Laboratoire->value] ?? 0) + $examsTotal;
        }

        $prescriptionsTotal = $this->prescriptionsTotal($patient, $visit);

        if ($prescriptionsTotal > 0) {
            $breakdown[ActCategory::Pharmacie->value] = ($breakdown[ActCategory::Pharmacie->value] ?? 0) + $prescriptionsTotal;
        }

        $pharmacyTotal = $prescriptionsTotal;

        $admissions = $patient->admissions()
            ->where('admitted_at', '>=', $visit->arrived_at)
            ->with(['dayLines', 'room'])
            ->get()
            ->filter(fn (Admission $admission) => $admission->visit_id === null || $admission->visit_id === $this->visit_id)
            ->values();

        foreach ($admissions as $admission) {
            if ($admission->discharged_at === null) {
                $admission->syncDayLines();
                $admission->load('dayLines');
            }
        }

        $stayTotal = $admissions->sum(
            fn (Admission $admission) => $admission->dayLines->sum('amount')
        );

        if ($stayTotal > 0) {
            $breakdown[ActCategory::Hospitalisation->value] = ($breakdown[ActCategory::Hospitalisation->value] ?? 0) + $stayTotal;
        }

        $agreedStay = $this->agreed_stay_amount !== null
            ? (int) $this->agreed_stay_amount
            : $stayTotal;

        $subtotal = $actsTotal + $pharmacyTotal + $agreedStay;

        $isInsured = (bool) $patient->insurance_company_id;

        if ($isInsured) {
            $rate = ((float) $this->ticket_moderateur) / 100;
            $ticket = (int) round($actsTotal * $rate);
            $patientAmount = $ticket + $pharmacyTotal + $agreedStay;
            $insuranceAmount = $actsTotal - $ticket;
        } else {
            $patientAmount = $actsTotal + $pharmacyTotal + $agreedStay;
            $insuranceAmount = 0;
        }

        $this->update([
            'subtotal' => $subtotal,
            'insurance_amount' => $insuranceAmount,
            'patient_amount' => $patientAmount,
            'breakdown' => $breakdown,
        ]);
    }

/**
 * Coût des ordonnances de la venue : la pharmacie n'est pas un acte assujetti
 * au ticket modérateur et n'est donc pas incluse dans `$actsTotal`.
 *
 * Comme les examens, une ordonnance appartient à la consultation qui l'a
 * prescrite. Le filtre par date seule laisserait remonter sur la facture d'une
 * consultation ancienne toutes les ordonnances des consultations suivantes, à
 * son propre taux.
 */
private function prescriptionsTotal(Patient $patient, Visit $visit): int
{
    $prescriptions = $patient->prescriptions()
        ->where(function ($query) use ($visit) {
            $query->where('visit_id', $visit->id)
                ->orWhere(function ($inner) use ($visit) {
                    // Ordonnance saisie avant l'existence du lien : imputée à
                    // la consultation ouverte au moment de la demande.
                    $inner->whereNull('visit_id')
                        ->where('requested_at', '>=', $visit->arrived_at);
                });
        })
        ->with('items')
        ->get();

    return (int) $prescriptions->sum(
        fn (Prescription $prescription) => $prescription->items->sum(
            fn (PrescriptionItem $item) => (int) $item->quantity * (int) $item->unit_price
        )
    );
}

    public function markAsAwaitingPayment(): void
    {
        $this->recalculate();
        $this->update(['status' => self::STATUS_AWAITING_PAYMENT]);
    }

    /**
     * Enregistre un encaissement et rapproche la facture.
     *
     * Un encaissement partiel est autorisé. En revanche un montant supérieur
     * au solde restant est refusé, et non tronqué en silence : un caissier qui
     * se trompe de montant doit l'apprendre, sinon l'écart disparaît du
     * dossier et le rapport de caisse devient faux sans aucun signe visible.
     */
    public function settle(int $amount, User $cashier, ?string $method, ?Payment $payment = null): self
    {
        if ($amount <= 0) {
            throw new PaymentRefusedException('Le montant encaissé doit être supérieur à zéro.');
        }

        if ($amount > $this->remaining_amount) {
            throw new PaymentRefusedException(
                'Le montant encaissé dépasse le solde restant de '.$this->remaining_amount.' FCFA.'
            );
        }

        $paid = (int) $this->paid_amount + $amount;
        $remaining = max(0, (int) $this->patient_amount - $paid);

        $status = match (true) {
            $remaining === 0 => self::STATUS_PAID,
            $paid > 0 => self::STATUS_PARTIAL,
            default => $this->status,
        };

        $this->update([
            'paid_amount' => $paid,
            'status' => $status,
            'cashier_id' => $cashier->id,
            'paid_at' => $status === self::STATUS_PAID ? ($this->paid_at ?? now()) : null,
        ]);

        if ($payment) {
            $payment->update(['invoice_id' => $this->id]);
        }

        return $this;
    }

    public function markAsPaid(User $cashier): void
    {
        $this->recalculate();
        $this->settle($this->remaining_amount, $cashier);
    }

    public function markAsPrinted(): void
    {
        $this->update(['printed_at' => now()]);
    }
}
