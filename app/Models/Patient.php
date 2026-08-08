<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'first_name',
        'last_name',
        'sex',
        'birth_date',
        'phone',
        'coverage',
        'insurance_company_id',
        'allergies',
        'guardian_name',
        'emergency_contact',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function insuranceCompany(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(Admission::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getInitialsAttribute(): string
    {
        return collect([$this->first_name, $this->last_name])
            ->filter()
            ->map(fn (string $part) => Str::upper($part[0]))
            ->implode('');
    }

    public function getIsInsuredAttribute(): bool
    {
        return $this->coverage === 'Assurance' && $this->insurance_company_id !== null;
    }

    public static function generateCode(): string
    {
        do {
            $code = 'CSM-'.random_int(1000, 9999);
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
