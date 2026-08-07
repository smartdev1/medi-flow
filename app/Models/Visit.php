<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'arrived_at',
        'orientation',
        'reason',
        'registered_by',
        'status',
        'urgent',
        'discharged_at',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'urgent' => 'boolean',
        'discharged_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function getStatusToneAttribute(): string
    {
        return match ($this->status) {
            'En consultation' => 'brand',
            'Prioritaire' => 'danger',
            'Orienté' => 'success',
            default => 'neutral',
        };
    }

    public function scopeActive($query)
    {
        return $query->whereNull('discharged_at');
    }

    public function isClosed(): bool
    {
        return $this->discharged_at !== null;
    }
}
