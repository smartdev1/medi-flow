<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exam extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_SAMPLE = 'En attente de prélèvement';
    public const STATUS_IN_PROGRESS = 'Résultat en cours';
    public const STATUS_READY = 'Résultat prêt';
    public const STATUS_SENT = 'Envoyé au médecin';

    protected $fillable = [
        'patient_id',
        'name',
        'status',
        'result',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'brand',
            self::STATUS_READY => 'success',
            default => 'neutral',
        };
    }

    public function getNextActionAttribute(): ?string
    {
        return match ($this->status) {
            self::STATUS_AWAITING_SAMPLE => 'Enregistrer le prélèvement',
            self::STATUS_IN_PROGRESS => 'Saisir le résultat',
            self::STATUS_READY => 'Envoyer au médecin',
            default => null,
        };
    }

    public function advance(): void
    {
        $this->update(['status' => match ($this->status) {
            self::STATUS_AWAITING_SAMPLE => self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => self::STATUS_READY,
            self::STATUS_READY => self::STATUS_SENT,
            default => $this->status,
        }]);
    }
}
