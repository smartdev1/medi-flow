<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SurgeryOperativeReportController extends Controller
{
    /**
     * Store the operative report for a completed surgery.
     */
    public function store(Request $request, Surgery $surgery): RedirectResponse
    {
        if ($surgery->status !== Surgery::STATUS_COMPLETED) {
            return redirect()->back()->with("error", "Le compte-rendu ne peut être rédigé que pour une chirurgie terminée.");
        }

        $validated = $request->validate([
            "operative_report" => ["required", "string", "min:50"],
        ]);

        $surgery->update([
            "operative_report" => $validated["operative_report"],
            "operative_report_by" => Auth::id(),
            "operative_report_at" => now(),
        ]);

        $surgery->recordAudit("report_submitted", ["operative_report" => null], [
            "operative_report" => $validated["operative_report"],
            "operative_report_by" => Auth::id(),
            "operative_report_at" => $surgery->operative_report_at->toISOString(),
        ], $request);

        return redirect()->back()->with("success", "Compte-rendu opératoire enregistré avec succès.");
    }
}
