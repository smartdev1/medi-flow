<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDayLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_id',
        'day_date',
        'amount',
    ];

    protected $casts = [
        'day_date' => 'date',
        'amount' => 'integer',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }
}
