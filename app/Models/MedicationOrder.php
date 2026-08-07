<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicationOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'En attente de réception';
    public const STATUS_RECEIVED = 'Reçue';

    protected $fillable = [
        'reference',
        'requested_by',
        'status',
        'ordered_at',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MedicationOrderItem::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getStatusToneAttribute(): string
    {
        return $this->status === self::STATUS_RECEIVED ? 'success' : 'warning';
    }

    public function getItemsSummaryAttribute(): string
    {
        return $this->items->map(fn (MedicationOrderItem $item) => "{$item->medication->name} × {$item->quantity}")->implode(' · ');
    }

    public function markReceived(): void
    {
        $this->loadMissing('items.medication');

        foreach ($this->items as $item) {
            $item->medication->increment('stock_quantity', $item->quantity);
        }

        $this->update(['status' => self::STATUS_RECEIVED, 'received_at' => now()]);
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'CMD-'.random_int(1000, 9999);
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
