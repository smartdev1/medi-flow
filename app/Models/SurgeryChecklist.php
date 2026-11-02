<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgeryChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        "surgery_id",
        "phase",
        "checked_by",
        "checked_at",
        "items",
        "notes",
    ];

    protected $casts = [
        "checked_at" => "datetime",
        "items" => "array",
    ];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "checked_by");
    }

    public function scopeForPhase($query, string $phase)
    {
        return $query->where("phase", $phase);
    }
}
