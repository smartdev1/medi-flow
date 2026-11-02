<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgeryChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurgeryChecklistController extends Controller
{
    private const PHASES = ["sign_in", "time_out", "sign_out"];

    /**
     * Store a checklist phase for a surgery.
     */
    public function store(Request $request, Surgery $surgery, string $phase): RedirectResponse
    {
        if (!in_array($phase, self::PHASES)) {
            abort(404, "Phase de checklist invalide.");
        }

        // Authorization for Sign In: only assigned anesthetist (or any medecin if none assigned)
        if ($phase === "sign_in" && $surgery->anesthetist_id !== null && $surgery->anesthetist_id !== $request->user()->id) {
            return redirect()->back()->with("error", "Seul l'anesthésiste assigné peut remplir la checklist Sign In.");
        }

        $existing = $surgery->checklists()->where("phase", $phase)->exists();
        if ($existing) {
            return redirect()->back()->with("error", "Cette phase de checklist a déjà été complétée.");
        }

        $validated = $request->validate([
            "items" => ["required", "array", "min:1"],
            "items.*" => ["boolean"],
            "notes" => ["nullable", "string"],
        ]);

        $checklist = $surgery->checklists()->create([
            "phase" => $phase,
            "checked_by" => Auth::id(),
            "checked_at" => now(),
            "items" => $validated["items"],
            "notes" => $validated["notes"] ?? null,
        ]);

        $surgery->recordAudit("checklist_completed", null, [
            "phase" => $phase,
            "items" => $validated["items"],
            "notes" => $validated["notes"] ?? null,
            "checklist_id" => $checklist->id,
        ], $request);

        $phaseLabels = [
            "sign_in" => "Sign In",
            "time_out" => "Time Out",
            "sign_out" => "Sign Out",
        ];

        return redirect()
            ->back()
            ->with("success", "Checklist " . $phaseLabels[$phase] . " enregistrée avec succès.");
    }
}
