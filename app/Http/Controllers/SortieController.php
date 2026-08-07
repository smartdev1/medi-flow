<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use App\Support\PatientTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SortieController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $request->integer('patient')
            ? Patient::with(['visits', 'payments', 'consultations', 'exams', 'prescriptions.items', 'admissions.room'])->find($request->integer('patient'))
            : null;

        $activeVisit = $patient?->visits->whereNull('discharged_at')->sortByDesc('arrived_at')->first();

        // Charger les patients avec des visites actives (prêts pour la sortie)
        $patientsReadyForDischarge = Patient::with(['visits'])
            ->whereHas('visits', function($query) {
                $query->active(); // Utilise la portée pour filtrer les visites actives
            })
            ->orderBy('first_name')
            ->get();

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('sortie.show', [
            'patient' => $patient,
            'activeVisit' => $activeVisit,
            'timeline' => $patient ? PatientTimeline::build($patient) : [],
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'patientsReadyForDischarge' => $patientsReadyForDischarge,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function close(Visit $visit): RedirectResponse
    {
        $visit->update(['discharged_at' => now()]);

        return redirect()
            ->route('sortie.index', ['patient' => $visit->patient_id])
            ->with('success', "Dossier de {$visit->patient->full_name} clôturé.");
    }
}
