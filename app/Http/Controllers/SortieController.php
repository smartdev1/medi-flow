<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
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
            ? Patient::with(['visits', 'visits.admissions', 'payments', 'consultations', 'exams', 'prescriptions.items', 'admissions.room'])->find($request->integer('patient'))
            : null;

        $activeVisit = $patient?->visits->whereNull('discharged_at')->sortByDesc('arrived_at')->first();

        // Determine whether the active visit has a medically-discharged admission
        // that still awaits depiquage validation by nursing aides.
        // Uses the eager-loaded visits.admissions collection to avoid an extra query.
        $depiquagePending = false;
        if ($activeVisit) {
            $depiquagePending = $activeVisit->admissions
                ->contains(fn ($a) => $a->discharged_at && ! $a->depiquage_valide_par);
        }

        $activeInvoice = $activeVisit
            ? Invoice::where('visit_id', $activeVisit->id)->where('status', '!=', Invoice::STATUS_PAID)->first()
            : null;

        // Patients dont le dossier est prêt à être clôturé : consultation
        // terminée, ou séjour dont la sortie médicale a été enregistrée.
        //
        // On ne passe pas par le scope `active` de Visit : il exclut
        // précisément le statut « Consultation terminée », alors que c'est
        // lui qui qualifie un dossier de prêt à sortir. La seule condition
        // est que la visite ne soit pas encore clôturée.
        $patientsReadyForDischarge = Patient::with(['visits.admissions'])
            ->whereHas('visits', fn ($query) => $query->whereNull('discharged_at'))
            ->orderBy('first_name')
            ->get()
            ->filter(fn (Patient $p) => $p->visits->contains(fn (Visit $visit) => $this->isReadyForClosure($visit)))
            ->values();

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('sortie.show', [
            'patient' => $patient,
            'activeVisit' => $activeVisit,
            'activeInvoice' => $activeInvoice,
            'depiquagePending' => $depiquagePending,
            'timeline' => $patient ? PatientTimeline::build($patient) : [],
            'patientOptions' => Patient::orderBy('first_name')->get(),
            'patientsReadyForDischarge' => $patientsReadyForDischarge,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    /**
     * Un dossier est prêt à être clôturé lorsque :
     * - Le patient a quitté l'unité (hospitalisation ou observation) ET le
     *   dépiquage a été validé par les aides-soignantes ; ou
     * - La consultation est terminée sans admission.
     */
    private function isReadyForClosure(Visit $visit): bool
    {
        $dischargedAdmissions = $visit->admissions->whereNotNull('discharged_at');

        if ($dischargedAdmissions->isNotEmpty()) {
            // AC2: All medically-discharged admissions must have depiquage validated.
            return $dischargedAdmissions->every(fn ($a) => $a->depiquage_valide_par !== null);
        }

        // Consultation-only patients (no admissions) — unaffected by depiquage.
        return $visit->status === Visit::STATUS_CONSULTATION_CLOSED;
    }

    /**
     * Sortie du patient — invariant 4.
     *
     * Refusée tant que la facture porte un solde restant. Le médecin, lui, peut
     * clôturer sa consultation sans que la caisse ait soldé : les deux gestes
     * sont distincts et le second est le seul qui libère le patient.
     *
     * Refusée également tant que le dépiquage du patient (hospitalisation ou
     * observation) n'a pas été validé par les aides-soignantes.
     */
    public function close(Visit $visit): RedirectResponse
    {
        // AC1: Guard — depiquage must be validated before administrative closure
        // for any patient coming from hospitalisation or observation.
        $admissionNeedingDepiquage = $visit->admissions()
            ->whereNotNull('discharged_at')
            ->whereNull('depiquage_valide_par')
            ->exists();

        if ($admissionNeedingDepiquage) {
            return redirect()
                ->route('sortie.index', ['patient' => $visit->patient_id])
                ->with('error', "Clôture impossible : le dépiquage du patient n'a pas été validé par les aides-soignantes.");
        }

        $invoice = Invoice::where('visit_id', $visit->id)
            ->where('status', '!=', Invoice::STATUS_PAID)
            ->first();

        if ($invoice) {
            $remaining = max(0, (int) $invoice->patient_amount - (int) $invoice->paid_amount);
            $threshold = (int) config('clinic.discharge_unpaid_threshold', 0);

            if ($remaining > $threshold) {
                return redirect()
                    ->route('sortie.index', ['patient' => $visit->patient_id])
                    ->with('error', "Clôture impossible : il reste ".number_format($remaining, 0, ',', ' ')." FCFA à encaisser à la caisse pour ce dossier.");
            }
        }

        $visit->update(['discharged_at' => now()]);

        return redirect()
            ->route('sortie.index', ['patient' => $visit->patient_id])
            ->with('success', "Dossier de {$visit->patient->full_name} clôturé.");
    }
}
