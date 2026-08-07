<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Prescription extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_DISPENSE = 'En attente de délivrance';
    public const STATUS_DISPENSED = 'Délivrée';

    protected $fillable = [
        'patient_id',
        'prescribed_by',
        'stock_status',
        'status',
        'requested_at',
        'dispensed_by',
        'dispensed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'dispensed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function prescribedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function getMedicationsSummaryAttribute(): string
    {
        return $this->items->map(fn (PrescriptionItem $item) => "{$item->name} × {$item->quantity}")->implode(' · ');
    }

    public function getTotalAmountAttribute(): int
    {
        return $this->items->sum(fn (PrescriptionItem $item) => $item->quantity * $item->unit_price);
    }

    public function getFormattedTotalAmountAttribute(): string
    {
        return number_format($this->total_amount, 0, ',', ' ').' FCFA';
    }

    public function getStatusToneAttribute(): string
    {
        return $this->status === self::STATUS_DISPENSED ? 'success' : 'neutral';
    }

    public function getStockToneAttribute(): string
    {
        return Str::startsWith($this->stock_status, 'Rupture') ? 'danger' : 'success';
    }

    public function getNextActionAttribute(): ?string
    {
        return $this->status === self::STATUS_AWAITING_DISPENSE ? 'Délivrer' : null;
    }

    public function hasSufficientStock(): bool
    {
        return $this->items->every(
            fn (PrescriptionItem $item) => ! $item->medication || $item->quantity <= $item->medication->stock_quantity
        );
    }

    public function dispense(User $pharmacist): int
    {
        $this->loadMissing('items.medication');

        $total = 0;

        foreach ($this->items as $item) {
            $item->medication?->decrement('stock_quantity', $item->quantity);
            $total += $item->quantity * $item->unit_price;
        }

        $this->update([
            'status' => self::STATUS_DISPENSED,
            'dispensed_by' => $pharmacist->id,
            'dispensed_at' => now(),
        ]);

        return $total;
    }
}
