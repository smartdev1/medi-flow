<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Versement journalier : ce qui remonte effectivement à la caisse centrale.
 *
 * Indépendant de la clôture de vacation, puisque le versement peut intervenir
 * le lendemain. Rattaché aux vacations et non à une date civile, car une
 * vacation de 22h à 6h est versée le lendemain mais couvre deux dates.
 */
class Versement extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'cashier_id',
        'amount',
        'method',
        'reference',
        'deposited_at',
        'deposited_by',
        'validated_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'integer',
        'deposited_at' => 'datetime',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function depositedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deposited_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(CashierSession::class, 'versement_session');
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 0, ',', ' ').' FCFA';
    }

    public function isValidated(): bool
    {
        return $this->validated_by !== null;
    }
}
