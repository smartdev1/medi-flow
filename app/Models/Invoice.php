<?php

namespace App\Models;

use App\Enums\ActCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
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

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAwaitingPayment($query)
    {
        return $query->where('status', self::STATUS_AWAITING_PAYMENT);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->status === self::STATUS_AWAITING_PAYMENT;
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
            self::STATUS_AWAITING_PAYMENT => 'warning',
            default => 'neutral',
        };
    }

    public function recalculate(): void
    {
        $visit = $this->visit->fresh() ?? $this->visit;
        $patient = $this->patient->fresh() ?? $this->patient;

        if (! $visit->relationLoaded('act')) {
            $visit->load('act');
        }

        $subtotal = 0;
        $insuranceAmount = 0;
        $patientAmount = 0;
        $breakdown = [];

        $insuranceRate = 0;
        if ($patient->insurance_company_id && $patient->insuranceCompany) {
            $insuranceRate = $patient->insuranceCompany->rate / 100;
        }

        if ($visit->act) {
            $price = $patient->insurance_company_id && $visit->act->insured_price
                ? $visit->act->insured_price
                : $visit->act->base_price;
            $subtotal += $price;
            $insuranceAmount += (int) ($price * $insuranceRate);
            $patientAmount += $price - (int) ($price * $insuranceRate);

            if ($price > 0) {
                $category = $visit->act->category instanceof ActCategory
                    ? $visit->act->category
                    : ActCategory::Consultation;
                $breakdown[$category->value] = ($breakdown[$category->value] ?? 0) + $price;
            }
        }

        $examsTotal = $patient->exams->sum('price');
        $subtotal += $examsTotal;
        $insuranceAmount += (int) ($examsTotal * $insuranceRate);
        $patientAmount += $examsTotal - (int) ($examsTotal * $insuranceRate);

        if ($examsTotal > 0) {
            $breakdown[ActCategory::Laboratoire->value] = ($breakdown[ActCategory::Laboratoire->value] ?? 0) + $examsTotal;
        }

        $admissions = $patient->admissions()
            ->where('admitted_at', '>=', $visit->arrived_at)
            ->with('dayLines')
            ->get();

        $admissionTotal = $admissions->sum(
            fn ($admission) => $admission->dayLines->sum('amount')
        );

        $subtotal += $admissionTotal;
        $insuranceAmount += (int) ($admissionTotal * $insuranceRate);
        $patientAmount += $admissionTotal - (int) ($admissionTotal * $insuranceRate);

        if ($admissionTotal > 0) {
            $breakdown[ActCategory::Hospitalisation->value] = ($breakdown[ActCategory::Hospitalisation->value] ?? 0) + $admissionTotal;
        }

        $this->update([
            'subtotal' => $subtotal,
            'insurance_amount' => $insuranceAmount,
            'patient_amount' => $patientAmount,
            'breakdown' => $breakdown,
        ]);
    }

    public function markAsAwaitingPayment(): void
    {
        $this->recalculate();
        $this->update(['status' => self::STATUS_AWAITING_PAYMENT]);
    }

    public function markAsPaid(User $cashier): void
    {
        $this->recalculate();

        $this->update([
            'status' => self::STATUS_PAID,
            'paid_amount' => $this->patient_amount,
            'cashier_id' => $cashier->id,
            'paid_at' => now(),
        ]);
    }

    public function markAsPrinted(): void
    {
        $this->update(['printed_at' => now()]);
    }
}
