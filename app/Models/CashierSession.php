<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function isActive(): bool
    {
        return $this->closed_at === null;
    }

    public function duration(): ?CarbonInterval
    {
        if ($this->closed_at) {
            return $this->opened_at->diffAsCarbonInterval($this->closed_at);
        }

        return null;
    }

    public function cashVariance(): ?int
    {
        if ($this->closed_at === null) {
            return null;
        }

        $paidSum = $this->payments()
            ->where('status', Payment::STATUS_PAID)
            ->sum('amount');

        return $this->opening_balance + $paidSum - $this->closing_balance;
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
