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
        'visit_id',
        'room_id',
        'bed_number',
        'reason',
        'attending',
        'status',
        'admitted_at',
        'discharged_at',
        'depiquage_valide_par',
        'depiquage_valide_le',
        'room_released_at',
    ];

    protected $casts = [
        'admitted_at' => 'datetime',
        'discharged_at' => 'datetime',
        'depiquage_valide_le' => 'datetime',
        'depiquage_valide_par' => 'integer',
        'room_released_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function depiquageValidePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'depiquage_valide_par');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('discharged_at');
    }

    public function isDepiquageValide(): bool
    {
        return $this->depiquage_valide_par !== null;
    }

    public function canBeDepiqued(): bool
    {
        return $this->discharged_at !== null && ! $this->isDepiquageValide();
    }

    public function canBeLiberee(): bool
    {
        return $this->isDepiquageValide()
            && $this->discharged_at !== null
            && $this->room_released_at === null;
    }

    public function isRoomReleased(): bool
    {
        return $this->room_released_at !== null;
    }

    public function dayLines(): HasMany
    {
        return $this->hasMany(AdmissionDayLine::class);
    }

    /**
     * Prix de la journée pour cette admission, figé au jour de l'admission
     * afin qu'une révision tarifaire ultérieure ne modifie pas les lignes
     * déjà facturées.
     */
    public function dailyRate(): int
    {
        return (int) ($this->dayLines->max('amount') ?: ($this->room?->daily_rate ?: 0));
    }

    public function isObservation(): bool
    {
        return $this->status === self::STATUS_OBSERVATION;
    }

    public function isDischargePlanned(): bool
    {
        return $this->status === self::STATUS_DISCHARGE_PLANNED;
    }

    /**
     * Unité de prise en charge d'un séjour, y compris lorsque le statut a été
     * remplacé par « Sortie prévue ». La visite rattachée fait foi ; à défaut,
     * une admission déjà terminée conserve le statut de sortie.
     */
    public function unit(): string
    {
        $visitStatus = $this->visit?->status;

        return match ($visitStatus) {
            Visit::STATUS_OBSERVATION, Visit::STATUS_OBSERVATION_REQUESTED => self::STATUS_OBSERVATION,
            Visit::STATUS_HOSPITALIZED, Visit::STATUS_HOSPITALIZATION_REQUESTED => self::STATUS_STABLE,
            default => $this->status,
        };
    }

    /**
     * Sortie planifiée : étape intermédiaire entre le clinique et la sortie
     * effective. Le dossier reste ouvert et la facturation continue.
     */
    public function planDischarge(): void
    {
        if ($this->discharged_at) {
            return;
        }

        $this->update(['status' => self::STATUS_DISCHARGE_PLANNED]);

        $this->syncDayLines();
    }

    /**
     * Annule une sortie planifiée : le patient reste hospitalisé.
     */
    public function cancelPlannedDischarge(): void
    {
        if (! $this->isDischargePlanned() || $this->discharged_at) {
            return;
        }

        $this->update([
            'status' => $this->unit() === self::STATUS_OBSERVATION
                ? self::STATUS_OBSERVATION
                : self::STATUS_STABLE,
        ]);
    }

    /**
     * Sortie effective : clôture du séjour, génération des lignes de
     * facturation et bascule de la visite vers son état de sortie.
     */
    public function discharge(): void
    {
        if ($this->discharged_at) {
            return;
        }

        $unit = $this->unit();

        $this->update(['discharged_at' => now()]);

        $this->syncDayLines();

        $this->visit?->update([
            'status' => $unit === self::STATUS_OBSERVATION
                ? Visit::STATUS_RETURNED_FROM_OBSERVATION
                : Visit::STATUS_CONSULTATION_CLOSED,
        ]);
    }

    /**
     * (Ré)génère les lignes de facturation du séjour : une ligne par journée
     * écoulée, du jour d'admission au jour de sortie (ou aujourd'hui si le
     * patient est encore hospitalisé).
     *
     * Appelée à l'admission, à la sortie et à chaque consultation du tableau
     * d'accueil ou de la facture : sans cela, un séjour en cours resterait
     * facturé à la seule journée d'admission. L'opération est un no-op quand
     * les lignes sont déjà à jour.
     */
    public function syncDayLines(): void
    {
        $start = $this->admitted_at->copy()->startOfDay();
        $end = ($this->discharged_at ?? now())->copy()->startOfDay();
        $days = $start->diffInDays($end) + 1;

        $dailyRate = (int) ($this->room?->daily_rate ?: 0);

        if ($dailyRate <= 0) {
            return;
        }

        $existing = $this->dayLines()->orderBy('day_date')->get(['day_date', 'amount']);

        // Le prix de la journée reste figé à la première facturation : une
        // révision tarifaire ultérieure ne doit pas modifier un séjour en cours.
        $rate = $existing->isNotEmpty() ? (int) $existing->max('amount') : $dailyRate;

        $expectedDates = collect(range(0, $days - 1))
            ->map(fn (int $offset) => $start->copy()->addDays($offset)->toDateString());

        if ($existing->count() === $days
            && $existing->pluck('day_date')->all() === $expectedDates->all()
            && $existing->every(fn (AdmissionDayLine $line) => (int) $line->amount === $rate)
        ) {
            return;
        }

        $this->dayLines()->delete();

        for ($i = 0; $i < $days; $i++) {
            $this->dayLines()->create([
                'day_date' => $expectedDates[$i],
                'amount' => $rate,
            ]);
        }
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
}
