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
        'is_cash_point',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'notes',
        'emergency_closed_by',
        'emergency_closed_at',
        'emergency_closure_reason',
    ];

    protected static function newFactory()
    {
        return \Database\Factories\CashierSessionFactory::new();
    }

    protected $casts = [
        'is_cash_point' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'integer',
        'closing_balance' => 'integer',
        'emergency_closed_at' => 'datetime',
        'emergency_closed_by' => 'integer',
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

    /**
     * Theoretical closing balance = opening_balance + all settled payments
     * (PAID + PARTIAL) made during this session. This is what the cashier
     * should have before counting physical cash.
     */
    public function theoreticalBalance(): int
    {
        return (int) ($this->opening_balance + $this->settledAmount());
    }

    public function cashVariance(): ?int
    {
        if ($this->closed_at === null || $this->closing_balance === null) {
            return null;
        }

        return $this->opening_balance + $this->settledAmount() - $this->closing_balance;
    }

    public function isEmergencyClosed(): bool
    {
        return $this->emergency_closed_by !== null;
    }

    public function canBeEmergencyClosed(): bool
    {
        return $this->isActive() && ! $this->isEmergencyClosed();
    }

    public function pendingAudit(): bool
    {
        return $this->isEmergencyClosed() && $this->closing_balance === null;
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

    /**
     * Vacances du point de vente pharmacie.
     *
     * Elles portent des fonds distincts de ceux de la caisse : un relevé qui
     * les confondrait rendrait le rapprochement faux sans erreur visible.
     */
    public function scopeCashPoint($query)
    {
        return $query->where('is_cash_point', true);
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
