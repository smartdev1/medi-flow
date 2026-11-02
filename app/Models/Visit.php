<?php

namespace App\Models;

use App\Models\Act;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'arrived_at',
        'reason',
        // Sans cette colonne, l'acte choisi à l'accueil ou par le médecin était
        // silencieusement ignoré et la consultation revenait à 0 FCFA.
        'act_id',
        'registered_by',
        'status',
        'urgent',
        'ticket_moderateur',
        'surgery_type',
        'discharged_at',
        'vitals_taken_at',
        'vitals_taken_by',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'urgent' => 'boolean',
        'ticket_moderateur' => 'decimal:2',
        'discharged_at' => 'datetime',
        'vitals_taken_at' => 'datetime',
    ];

    // Status constants
    const STATUS_WAITING = 'En attente';
    const STATUS_URGENT = 'Prioritaire';
    const STATUS_IN_CONSULTATION = 'En consultation';
    const STATUS_CONSULTATION_CLOSED = 'Consultation terminée';
    const STATUS_HOSPITALIZATION_REQUESTED = 'Demande hospitalisation';
    const STATUS_OBSERVATION_REQUESTED = 'Demande observation';
    const STATUS_RETURNED_FROM_OBSERVATION = 'Retour observation';
    const STATUS_HOSPITALIZED = 'Hospitalisé';
    const STATUS_OBSERVATION = 'En observation';
    const STATUS_REFERRED = 'Orienté';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function vitalsTakenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vitals_taken_by');
    }

    public function act(): BelongsTo
    {
        return $this->belongsTo(Act::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    /**
     * Encaissements rattachés à la visite.
     *
     * `visit_id` est nullable en base : un paiement manuel saisi au guichet
     * peut ne relever d'aucune visite, et n'apparaîtra donc pas ici — ce qui
     * est le comportement voulu pour l'affichage d'un dossier.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function admissionRequests(): HasMany
    {
        return $this->hasMany(AdmissionRequest::class);
    }

    public function surgeries(): HasMany
    {
        return $this->hasMany(Surgery::class);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_CONSULTATION => 'brand',
            self::STATUS_URGENT => 'danger',
            self::STATUS_HOSPITALIZATION_REQUESTED,
            self::STATUS_OBSERVATION_REQUESTED,
            self::STATUS_HOSPITALIZED,
            self::STATUS_OBSERVATION,
            self::STATUS_RETURNED_FROM_OBSERVATION => 'warning',
            self::STATUS_CONSULTATION_CLOSED,
            self::STATUS_REFERRED => 'success',
            default => 'neutral',
        };
    }

    public function scopeActive($query)
    {
        return $query->whereNull('discharged_at')
            ->where('status', '!=', self::STATUS_CONSULTATION_CLOSED)
            ->where('status', '!=', self::STATUS_REFERRED);
    }

    /**
     * Admission en cours rattachée à cette visite, s'il y en a une.
     */
    public function activeAdmission(): ?Admission
    {
        return $this->admissions()->whereNull('discharged_at')->latest('admitted_at')->first()
            ?? $this->admissions()->whereNull('discharged_at')->first();
    }

    public function isClosed(): bool
    {
        return $this->discharged_at !== null;
    }
}
