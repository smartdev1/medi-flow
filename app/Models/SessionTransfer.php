<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Remise de fonds entre deux vacations.
 *
 * L'argent reste dans le coffre : c'est la responsabilité qui change de main.
 * Le caissier entrant ouvre sa vacation avec le montant qu'il a reçu, et ce
 * montant doit pouvoir être rattaché au comptage du caissier sortant.
 */
class SessionTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_session_id',
        'to_session_id',
        'from_cashier_id',
        'to_cashier_id',
        'amount',
        'transferred_at',
        'counted_by',
        'received_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'integer',
        'transferred_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function fromSession(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'from_session_id');
    }

    public function toSession(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'to_session_id');
    }

    public function fromCashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_cashier_id');
    }

    public function toCashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_cashier_id');
    }

    public function isConfirmed(): bool
    {
        return $this->received_at !== null;
    }

    /**
     * Le caissier entrant valide la réception du fonds.
     */
    public function confirmBy(User $receiver): void
    {
        if ($this->received_at) {
            return;
        }

        $this->update([
            'received_at' => now(),
            'to_cashier_id' => $receiver->id,
        ]);
    }
}
