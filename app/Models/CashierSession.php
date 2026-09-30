<?php

namespace App\Models;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class CashierSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'notes',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\CashierSessionFactory::new();
    }

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'integer',
        'closing_balance' => 'integer',
    ];

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'cashier_session_id');
    }

    public function transfersGiven(): HasMany
    {
        return $this->hasMany(SessionTransfer::class, 'from_session_id');
    }

    public function transferReceived(): HasOne
    {
        return $this->hasOne(SessionTransfer::class, 'to_session_id');
    }

    public function versements(): BelongsToMany
    {
        return $this->belongsToMany(Versement::class, 'versement_session');
    }

    public function isActive(): bool
    {
        return $this->closed_at === null;
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function duration(): ?CarbonInterval
    {
        if ($this->closed_at) {
            return $this->opened_at->diffAsCarbonInterval($this->closed_at);
        }

        return null;
    }

    /**
     * Encaissements réels de la vacation, toutes dates confondues.
     *
     * Une vacation de 22h à 6h encaisse après minuit : la rattacher à une
     * date civile fausserait le rapport du jour. La vacation est donc l'unité
     * de rapprochement.
     */
    public function settledAmount(): int
    {
        return (int) $this->payments()->settled()->sum('amount');
    }

    public function cashVariance(): ?int
    {
        if ($this->closed_at === null) {
            return null;
        }

        return $this->opening_balance + $this->settledAmount() - $this->closing_balance;
    }

    /**
     * Fonds transmis au caissier suivant lors du relevé. Le reliquat reste
     * au coffre et fera l'objet d'un versement.
     */
    public function transferredAmount(): int
    {
        return (int) $this->transfersGiven()->sum('amount');
    }

    /**
     * Fonds effectivement sortis du coffre de cette vacation : ce qui a été
     * transmis au caissier suivant (relève) et ce qui a été reversé à la
     * caisse centrale (versement).
     *
     * Les deux doivent être décomptés ensemble, sinon le même argent peut être
     * compté deux fois : d'abord remis à un collègue, puis versé en caisse.
     */
    public function remittedAmount(): int
    {
        return $this->transferredAmount()
            + (int) $this->versements()->sum('amount');
    }

    public function availableForRemittance(): int
    {
        if (! $this->isClosed()) {
            return 0;
        }

        return max(0, (int) $this->closing_balance - $this->remittedAmount());
    }

    public function scopeForCashier($query, int $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('closed_at');
    }

    public function scopeForDate($query, Carbon|string $date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date)->startOfDay();

        return $query->whereDate('opened_at', $date);
    }

    public function scopeClosed($query)
    {
        return $query->whereNotNull('closed_at');
    }
}
