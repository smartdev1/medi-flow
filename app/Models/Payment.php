<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PAID = 'Payé';
    public const STATUS_PARTIAL = 'Partiellement payé';
    public const STATUS_PENDING = 'En attente';
    public const STATUS_CANCELLED = 'Annulé';
    public const STATUS_REFUNDED = 'Remboursé';

    public const METHOD_CASH = 'Espèces';

    protected $fillable = [
        'patient_id',
        'visit_id',
        'invoice_id',
        'prescription_id',
        'act_id',
        'service',
        'base_amount',
        'taux',
        'amount',
        'method',
        'status',
        'cashier_id',
        'cashier_session_id',
    ];

    protected $casts = [
        'base_amount' => 'integer',
        'taux' => 'decimal:2',
        'amount' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function act(): BelongsTo
    {
        return $this->belongsTo(Act::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }

    public function scopeForCashier($query, int $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }

    public function scopeForCashierAndDate($query, int $cashierId, \DateTimeInterface|string $date)
    {
        return $query->where('cashier_id', $cashierId)->whereDate('created_at', $date);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ').' FCFA';
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'success',
            self::STATUS_PARTIAL => 'warning',
            self::STATUS_CANCELLED, self::STATUS_REFUNDED => 'danger',
            default => 'neutral',
        };
    }

    public function isSettled(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_PARTIAL], true);
    }

    public function isVoided(): bool
    {
        return in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_REFUNDED], true);
    }

    public function scopeForDate($query, \DateTimeInterface|string $date)
    {
        return $query->whereDate('created_at', $date);
    }

    /**
     * Encaissements réels : les annulations et remboursements ne mouvementent
     * pas la caisse.
     */
    public function scopeSettled($query)
    {
        return $query->whereIn('status', [self::STATUS_PAID, self::STATUS_PARTIAL]);
    }
}
