<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surgery extends Model
{
    use HasFactory, Auditable;

    const STATUS_SCHEDULED = "scheduled";
    const STATUS_CONSENT_SIGNED = "consent_signed";
    const STATUS_IN_PROGRESS = "in_progress";
    const STATUS_COMPLETED = "completed";
    const STATUS_CANCELLED = "cancelled";

    protected $fillable = [
        "visit_id",
        "patient_id",
        "surgeon_id",
        "anesthetist_id",
        "scheduled_at",
        "status",
        "surgery_type",
        "referring_doctor_name",
        "procedure_name",
        "pre_anesthetic_notes",
        "consent_signed_at",
        "consent_signed_by",
        "notes",
        "started_at",
        "completed_at",
        "operative_report",
        "operative_report_by",
        "operative_report_at",
        "recovery_started_at",
        "recovery_ended_at",
        "recovery_notes",
        "recovery_nurse_id",
    ];

    protected $casts = [
        "scheduled_at" => "datetime",
        "consent_signed_at" => "datetime",
        "started_at" => "datetime",
        "completed_at" => "datetime",
        "operative_report_at" => "datetime",
        "recovery_started_at" => "datetime",
        "recovery_ended_at" => "datetime",
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function surgeon(): BelongsTo
    {
        return $this->belongsTo(User::class, "surgeon_id");
    }

    public function anesthetist(): BelongsTo
    {
        return $this->belongsTo(User::class, "anesthetist_id");
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(SurgeryChecklist::class);
    }

    public function operativeReportBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "operative_report_by");
    }

    public function recoveryNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, "recovery_nurse_id");
    }
}
