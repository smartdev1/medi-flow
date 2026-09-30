<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consultation extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'En cours';
    public const STATUS_CLOSED = 'Clôturée';

    protected $fillable = [
        'patient_id',
        'visit_id',
        'doctor_id',
        'vitals',
        'notes',
        'status',
        'started_at',
        'closed_at',
    ];

    protected $casts = [
        'vitals' => 'array',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function getStatusToneAttribute(): string
    {
        return $this->status === self::STATUS_CLOSED ? 'success' : 'brand';
    }

    public function close(): void
    {
        $this->update(['status' => self::STATUS_CLOSED, 'closed_at' => now()]);

        // Clôturer une consultation clôturerait la visite ; sans cela la
        // visite resterait « En consultation » indéfiniment.
        $this->visit?->update(['status' => Visit::STATUS_CONSULTATION_CLOSED]);
    }
}
