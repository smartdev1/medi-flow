<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Traits\GuardsClosedVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurgeryRecoveryController extends Controller
{
    use GuardsClosedVisit;

    public function start(Request $request, Surgery $surgery): RedirectResponse
    {
        $this->abortIfVisitClosed($surgery->visit);
        if ($surgery->status !== Surgery::STATUS_COMPLETED) {
            return redirect()->back()->with("error", "La surveillance en salle de réveil ne peut commencer que pour une chirurgie terminée.");
        }

        if ($surgery->recovery_started_at !== null) {
            return redirect()->back()->with("error", "La surveillance a déjà commencé.");
        }

        $surgery->update([
            "recovery_started_at" => now(),
            "recovery_nurse_id" => Auth::id(),
        ]);

        $surgery->recordAudit("recovery_started", null, [
            "recovery_started_at" => $surgery->recovery_started_at->toISOString(),
            "recovery_nurse_id" => Auth::id(),
        ], $request);

        return redirect()->back()->with("success", "Surveillance en salle de réveil commencée.");
    }

    public function end(Request $request, Surgery $surgery): RedirectResponse
    {
        $this->abortIfVisitClosed($surgery->visit);
        if ($surgery->recovery_started_at === null) {
            return redirect()->back()->with("error", "La surveillance n'a pas encore commencé.");
        }

        if ($surgery->recovery_ended_at !== null) {
            return redirect()->back()->with("error", "La surveillance est déjà terminée.");
        }

        $validated = $request->validate([
            "recovery_notes" => ["required", "string", "min:10"],
        ]);

        $surgery->update([
            "recovery_ended_at" => now(),
            "recovery_notes" => $validated["recovery_notes"],
        ]);

        $surgery->recordAudit("recovery_ended", ["recovery_ended_at" => null, "recovery_notes" => null], [
            "recovery_ended_at" => $surgery->recovery_ended_at->toISOString(),
            "recovery_notes" => $validated["recovery_notes"],
        ], $request);

        return redirect()->back()->with("success", "Surveillance en salle de réveil terminée.");
    }
}
