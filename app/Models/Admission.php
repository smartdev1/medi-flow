<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    use HasFactory;

    public const STATUS_OBSERVATION = 'En observation';
    public const STATUS_STABLE = 'Stable';
    public const STATUS_DISCHARGE_PLANNED = 'Sortie prévue';

    protected $fillable = [
        'patient_id',
        'room_id',
        'bed_number',
        'reason',
        'attending',
        'status',
        'admitted_at',
        'discharged_at',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('discharged_at');
    }

    public function dayLines(): HasMany
    {
        return $this->hasMany(AdmissionDayLine::class);
    }

    public function generateDayLines(): void
    {
        if (is_null($this->discharged_at)) {
            return;
        }

        \DB::transaction(function () {
            $this->dayLines()->delete();

            $start = $this->admitted_at->copy()->startOfDay();
            $end = $this->discharged_at->copy()->startOfDay();
            $days = $start->diffInDays($end) + 1;
            $dailyRate = $this->room->daily_rate;

            for ($i = 0; $i < $days; $i++) {
                $this->dayLines()->create([
                    'day_date' => $start->copy()->addDays($i)->toDateString(),
                    'amount' => $dailyRate,
                ]);
            }
        });
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OBSERVATION => 'brand',
            self::STATUS_STABLE => 'success',
            self::STATUS_DISCHARGE_PLANNED => 'warning',
            default => 'neutral',
        };
    }

    public function discharge(): void
    {
        $this->update(['discharged_at' => now()]);
        $this->generateDayLines();
    }
}
