<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SurgeryController extends Controller
{
    /**
     * Schedule a new surgery for a visit.
     */
    public function store(Request $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validate([
            "procedure_name" => "required|string|max:255",
            "scheduled_at" => "nullable|date",
            "anesthetist_id" => "nullable|exists:users,id",
            "referring_doctor_name" => "nullable|string|max:255",
        ]);

        $surgery = Surgery::create([
            "visit_id" => $visit->id,
            "patient_id" => $visit->patient_id,
            "surgeon_id" => Auth::id(),
            "anesthetist_id" => $validated["anesthetist_id"] ?? null,
            "scheduled_at" => $validated["scheduled_at"] ?? null,
            "procedure_name" => $validated["procedure_name"],
            "surgery_type" => $visit->surgery_type,
            "referring_doctor_name" => $visit->surgery_type === "externe"
                ? ($validated["referring_doctor_name"] ?? null)
                : null,
            "status" => Surgery::STATUS_SCHEDULED,
        ]);

        $surgery->recordAudit("created", null, $surgery->toArray(), $request);

        return redirect()->back()->with("success", "Chirurgie programmée avec succès.");
    }

    /**
     * Update surgery schedule, anesthetist, notes, or procedure name.
     *
     * Only allowed when surgery is in "scheduled" or "consent_signed" status.
     */
    public function update(Request $request, Surgery $surgery): RedirectResponse
    {
        if (!in_array($surgery->status, [Surgery::STATUS_SCHEDULED, Surgery::STATUS_CONSENT_SIGNED])) {
            abort(Response::HTTP_FORBIDDEN, "Cette chirurgie ne peut plus être modifiée.");
        }

        $validated = $request->validate([
            "procedure_name" => "required|string|max:255",
            "scheduled_at" => "nullable|date",
            "anesthetist_id" => "nullable|exists:users,id",
            "notes" => "nullable|string",
        ]);

        $oldValues = $surgery->only(["procedure_name", "scheduled_at", "anesthetist_id", "notes"]);
        $surgery->update($validated);
        $surgery->recordAudit("updated", $oldValues, $surgery->fresh()->only(["procedure_name", "scheduled_at", "anesthetist_id", "notes"]), $request);

        return redirect()->back()->with("success", "Chirurgie mise à jour avec succès.");
    }

    /**
     * Cancel a scheduled surgery (does NOT delete — sets status to cancelled).
     *
     * Only allowed when surgery status is "scheduled".
     */
    public function destroy(Surgery $surgery): RedirectResponse
    {
        if ($surgery->status !== Surgery::STATUS_SCHEDULED) {
            abort(Response::HTTP_FORBIDDEN, "Seule une chirurgie programmée peut être annulée.");
        }

        $oldStatus = $surgery->status;
        $surgery->update(["status" => Surgery::STATUS_CANCELLED]);
        $surgery->recordAudit("status_changed", ["status" => $oldStatus], ["status" => Surgery::STATUS_CANCELLED]);

        return redirect()->back()->with("success", "Chirurgie annulée.");
    }

    /**
     * Start surgery (transition to in_progress).
     *
     * Requires Sign In checklist to be completed.
     */
    public function start(Request $request, Surgery $surgery): RedirectResponse
    {
        if ($surgery->status !== Surgery::STATUS_CONSENT_SIGNED) {
            return redirect()->back()->with("error", "La chirurgie doit avoir le consentement signé pour être démarrée.");
        }

        $hasSignIn = $surgery->checklists()->where("phase", "sign_in")->exists();
        if (!$hasSignIn) {
            return redirect()->back()->with("error", "La checklist Sign In doit être complétée avant de démarrer la chirurgie.");
        }

        $oldStatus = $surgery->status;
        $surgery->update([
            "status" => Surgery::STATUS_IN_PROGRESS,
            "started_at" => now(),
        ]);
        $surgery->recordAudit("status_changed", ["status" => $oldStatus], ["status" => Surgery::STATUS_IN_PROGRESS], $request);

        return redirect()->back()->with("success", "Chirurgie démarrée.");
    }

    /**
     * Complete surgery (transition to completed).
     *
     * Requires Sign Out checklist to be completed.
     */
    public function complete(Request $request, Surgery $surgery): RedirectResponse
    {
        if ($surgery->status !== Surgery::STATUS_IN_PROGRESS) {
            return redirect()->back()->with("error", "Seule une chirurgie en cours peut être terminée.");
        }

        $hasSignOut = $surgery->checklists()->where("phase", "sign_out")->exists();
        if (!$hasSignOut) {
            return redirect()->back()->with("error", "La checklist Sign Out doit être complétée avant de terminer la chirurgie.");
        }

        $oldStatus = $surgery->status;
        $surgery->update([
            "status" => Surgery::STATUS_COMPLETED,
            "completed_at" => now(),
        ]);
        $surgery->recordAudit("status_changed", ["status" => $oldStatus], ["status" => Surgery::STATUS_COMPLETED], $request);

        return redirect()->back()->with("success", "Chirurgie terminée.");
    }
}
