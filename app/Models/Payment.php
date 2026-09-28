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

    protected $fillable = [
        'patient_id',
        'visit_id',
        'prescription_id',
        'act_id',
        'service',
        'base_amount',
        'taux',
        'amount',
        'method',
        'status',
        'closed_at',
        'closed_by',
        'cashier_id',
        'cashier_session_id',
    ];

    protected $casts = [
        'base_amount' => 'integer',
        'taux' => 'decimal:2',
        'amount' => 'integer',
        'closed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }

    public function act(): BelongsTo
    {
        return $this->belongsTo(Act::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
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
            default => 'neutral',
        };
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function scopeForDate($query, \DateTimeInterface|string $date)
    {
        return $query->whereDate('created_at', $date);
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('closed_at');
    }

    public function scopeClosed($query)
    {
        return $query->whereNotNull('closed_at');
    }
}
