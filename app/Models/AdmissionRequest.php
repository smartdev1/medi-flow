<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'patient_id',
        'visit_id',
        'requested_by',
        'type',
        'reason',
        'status',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Normalise le type de demande.
     *
     * Les demandes créées avant la standardisation utilisent
     * « hospitalization » (anglais) : on les ramène à « hospitalisation »
     * pour que la comparaison reste fiable partout dans l'application.
     */
    public function getTypeAttribute($value): string
    {
        return $value === 'hospitalization' ? 'hospitalisation' : $value;
    }

    public function isObservation(): bool
    {
        return $this->type === 'observation';
    }

    public function approve(User $user): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'processed_by' => $user->id,
            'processed_at' => now(),
        ]);
    }

    public function reject(User $user): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'processed_by' => $user->id,
            'processed_at' => now(),
        ]);
    }
}
