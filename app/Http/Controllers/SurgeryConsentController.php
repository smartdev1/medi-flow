<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Traits\GuardsClosedVisit;
use Illuminate\Http\Request;

class SurgeryConsentController extends Controller
{
    use GuardsClosedVisit;

    public function preAnesthetic(Request $request, Surgery $surgery)
    {
        $this->abortIfVisitClosed($surgery->visit);
        $validated = $request->validate([
            "pre_anesthetic_notes" => ["required", "string", "min:10"],
        ]);

        $oldNotes = $surgery->pre_anesthetic_notes;
        $surgery->update([
            "pre_anesthetic_notes" => $validated["pre_anesthetic_notes"],
        ]);

        $surgery->recordAudit("pre_anesthetic_notes_filled", ["pre_anesthetic_notes" => $oldNotes], ["pre_anesthetic_notes" => $validated["pre_anesthetic_notes"]], $request);

        return redirect()->back()->with("success", "Notes pré-anesthésiques enregistrées.");
    }
}
