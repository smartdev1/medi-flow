<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    const TYPE_INDIVIDUELLE = 'individuelle';
    const TYPE_GROUPE = 'groupe';

    protected $fillable = [
        'ward',
        'number',
        'capacity',
        'type',
        'daily_rate',
        'maintenance',
    ];

    protected $casts = [
        'maintenance' => 'boolean',
    ];

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function activeAdmissions(): HasMany
    {
        return $this->admissions()->whereNull('discharged_at');
    }

    public function getOccupiedCountAttribute(): int
    {
        return $this->activeAdmissions->count();
    }

    public function getAvailableBedNumbersAttribute(): array
    {
        $occupied = $this->activeAdmissions->pluck('bed_number')->all();

        return array_values(array_diff(range(1, $this->capacity), $occupied));
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->maintenance) {
            return 'Maintenance';
        }

        return match (true) {
            $this->occupied_count === 0 => 'Disponible',
            $this->occupied_count < $this->capacity => 'Partiellement occupée',
            default => 'Complète',
        };
    }

    public function getStatusToneAttribute(): string
    {
        if ($this->maintenance) {
            return 'danger';
        }

        return match (true) {
            $this->occupied_count === 0 => 'success',
            $this->occupied_count < $this->capacity => 'warning',
            default => 'brand',
        };
    }
}
