<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurgeryConsentController extends Controller
{
    /**
     * Record pre-anesthetic evaluation notes.
     *
     * Only the assigned anesthetist may submit. If no anesthetist is assigned,
     * any authenticated medecin (already enforced by route middleware) may act.
     */
    public function preAnesthetic(Request $request, Surgery $surgery)
    {
        if ($surgery->anesthetist_id !== null
            && $surgery->anesthetist_id !== $request->user()->id) {
            abort(403, "Seul l'anesthésiste assigné peut remplir la consultation pré-anesthésique.");
        }

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

    /**
     * Sign the surgical consent form.
     *
     * Pre-anesthetic notes must be filled before consent can be signed.
     */
    public function signConsent(Request $request, Surgery $surgery)
    {
        if (empty($surgery->pre_anesthetic_notes)) {
            abort(422, "La consultation pré-anesthésique doit être remplie avant la signature du consentement.");
        }

        $validated = $request->validate([
            "consent_signed_by" => ["required", "string", "max:255"],
        ]);

        $surgery->update([
            "consent_signed_at" => now(),
            "consent_signed_by" => $validated["consent_signed_by"],
            "status" => Surgery::STATUS_CONSENT_SIGNED,
        ]);

        $surgery->recordAudit("consent_signed", null, [
            "consent_signed_by" => $validated["consent_signed_by"],
            "consent_signed_at" => $surgery->consent_signed_at->toISOString(),
            "status" => Surgery::STATUS_CONSENT_SIGNED,
        ], $request);

        return redirect()->back()->with("success", "Consentement signé avec succès.");
    }
}
