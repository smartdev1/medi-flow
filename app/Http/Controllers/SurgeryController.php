<?php

namespace App\Http\Controllers;

use App\Models\AdmissionRequest;
use App\Models\Invoice;
use App\Models\Surgery;
use App\Models\Visit;
use App\Traits\GuardsClosedVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SurgeryController extends Controller
{
    use GuardsClosedVisit;
    public function store(Request $request, Visit $visit): RedirectResponse
    {
        $this->abortIfVisitClosed($visit);
        $validated = $request->validate([
            "procedure_name" => "required|string|max:255",
            "scheduled_at" => "nullable|date",
            "anesthetist_id" => "nullable|exists:users,id",
            "referring_doctor_name" => "nullable|string|max:255",
            "cost" => "required|integer|min:0",
        ]);

        $surgery = Surgery::create([
            "visit_id" => $visit->id,
            "patient_id" => $visit->patient_id,
            "surgeon_id" => Auth::id(),
            "anesthetist_id" => $validated["anesthetist_id"] ?? null,
            "scheduled_at" => $validated["scheduled_at"] ?? null,
            "procedure_name" => $validated["procedure_name"],
            "cost" => $validated["cost"],
            "surgery_type" => $visit->surgery_type,
            "referring_doctor_name" => $visit->surgery_type === "externe"
                ? ($validated["referring_doctor_name"] ?? null)
                : null,
            "status" => Surgery::STATUS_SCHEDULED,
        ]);

        $surgery->recordAudit("created", null, $surgery->toArray(), $request);

        Invoice::forVisit($visit);

        return redirect()->back()->with("success", "Chirurgie programmée avec succès.");
    }

    public function update(Request $request, Surgery $surgery): RedirectResponse
    {
        $this->abortIfVisitClosed($surgery->visit);
        if ($surgery->status !== Surgery::STATUS_SCHEDULED) {
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

    public function destroy(Surgery $surgery): RedirectResponse
    {
        $this->abortIfVisitClosed($surgery->visit);
        if ($surgery->status !== Surgery::STATUS_SCHEDULED) {
            abort(Response::HTTP_FORBIDDEN, "Seule une chirurgie programmée peut être annulée.");
        }

        $oldStatus = $surgery->status;
        $surgery->update(["status" => Surgery::STATUS_CANCELLED]);
        $surgery->recordAudit("status_changed", ["status" => $oldStatus], ["status" => Surgery::STATUS_CANCELLED]);

        $visit = $surgery->visit;
        if ($visit) {
            Invoice::forVisit($visit);
        }

        return redirect()->back()->with("success", "Chirurgie annulée.");
    }

    public function start(Request $request, Surgery $surgery): RedirectResponse
    {
        $this->abortIfVisitClosed($surgery->visit);
        if ($surgery->status !== Surgery::STATUS_SCHEDULED) {
            return redirect()->back()->with("error", "Seule une chirurgie programmée peut être démarrée.");
        }

        $validated = $request->validate([
            "consent" => "accepted",
        ]);

        $oldStatus = $surgery->status;
        $surgery->update([
            "status" => Surgery::STATUS_IN_PROGRESS,
            "started_at" => now(),
            "consent_signed_at" => $surgery->consent_signed_at ?? now(),
            "consent_signed_by" => $surgery->consent_signed_by ?? Auth::user()->name,
        ]);
        $surgery->recordAudit("status_changed", ["status" => $oldStatus], ["status" => Surgery::STATUS_IN_PROGRESS], $request);

        return redirect()->back()->with("success", "Chirurgie démarrée.");
    }

    public function complete(Request $request, Surgery $surgery): RedirectResponse
    {
        $this->abortIfVisitClosed($surgery->visit);
        if ($surgery->status !== Surgery::STATUS_IN_PROGRESS) {
            return redirect()->back()->with("error", "Seule une chirurgie en cours peut être terminée.");
        }

        $oldStatus = $surgery->status;
        $surgery->update([
            "status" => Surgery::STATUS_COMPLETED,
            "completed_at" => now(),
        ]);
        $surgery->recordAudit("status_changed", ["status" => $oldStatus], ["status" => Surgery::STATUS_COMPLETED], $request);

        $visit = $surgery->visit;
        Invoice::forVisit($visit);
        $alreadyRequested = AdmissionRequest::where("visit_id", $visit->id)
            ->where("status", AdmissionRequest::STATUS_PENDING)
            ->exists();
        $alreadyAdmitted = $surgery->patient->admissions()
            ->whereNull("discharged_at")
            ->exists();

        if (!$alreadyRequested && !$alreadyAdmitted) {
            AdmissionRequest::create([
                "visit_id" => $visit->id,
                "patient_id" => $surgery->patient_id,
                "requested_by" => Auth::id(),
                "type" => "hospitalisation",
                "reason" => "Post-opératoire — " . $surgery->procedure_name,
                "status" => AdmissionRequest::STATUS_PENDING,
            ]);
        }

        return redirect()->back()->with("success", "Chirurgie terminée. Demande d'hospitalisation créée.");
    }

    public function updateCost(Request $request, Surgery $surgery): RedirectResponse
    {
        $this->abortIfVisitClosed($surgery->visit);
        $validated = $request->validate([
            "cost" => "required|integer|min:0",
        ]);

        $oldCost = $surgery->cost;
        $surgery->update(["cost" => $validated["cost"]]);
        $surgery->recordAudit("cost_updated", ["cost" => $oldCost], ["cost" => $validated["cost"]], $request);

        $invoice = $surgery->visit->invoice;
        if ($invoice) {
            $invoice->recalculate();
        }

        return redirect()->back()->with("success", "Montant chirurgie mis à jour.");
    }
}
