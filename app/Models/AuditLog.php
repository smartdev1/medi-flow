<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        "auditable_type",
        "auditable_id",
        "user_id",
        "action",
        "old_values",
        "new_values",
        "ip_address",
    ];

    protected $casts = [
        "old_values" => "array",
        "new_values" => "array",
    ];

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
