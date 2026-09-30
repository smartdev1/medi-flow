<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdmissionRequest;
use App\Models\Admission;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionController extends Controller
{
    public function store(StoreAdmissionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);
        $room = Room::findOrFail($data['room_id']);

        if ($patient->admissions()->whereNull('discharged_at')->exists()) {
            throw ValidationException::withMessages([
                'patient_id' => "{$patient->full_name} a déjà une admission en cours.",
            ]);
        }

        $bedNumber = $data['bed_number'] ?? $room->available_bed_numbers[0] ?? null;

        if ($room->maintenance || $bedNumber === null || ! in_array($bedNumber, $room->available_bed_numbers, false)) {
            throw ValidationException::withMessages([
                'room_id' => "La {$room->number} n'a plus de lit disponible.",
            ]);
        }

        $visit = $patient->visits()->active()->latest('arrived_at')->first();
        $isObservation = ($data['type'] ?? 'hospitalisation') === 'observation';

        $admission = DB::transaction(function () use ($patient, $room, $bedNumber, $data, $visit, $isObservation) {
            $admission = $patient->admissions()->create([
                'visit_id' => $visit?->id,
                'room_id' => $room->id,
                'bed_number' => $bedNumber,
                'reason' => $data['reason'],
                'attending' => $data['attending'] ?? null,
                'status' => $isObservation ? Admission::STATUS_OBSERVATION : Admission::STATUS_STABLE,
                'admitted_at' => now(),
            ]);

            // La journée d'admission est facturée dès l'entrée.
            $admission->syncDayLines();

            $visit?->update([
                'status' => $isObservation
                    ? \App\Models\Visit::STATUS_OBSERVATION
                    : \App\Models\Visit::STATUS_HOSPITALIZED,
            ]);

            if ($visit) {
                Invoice::forVisit($visit);
            }

            return $admission;
        });

        $unit = $isObservation ? 'observation' : 'hospitalisation';

        return redirect()
            ->route($isObservation ? 'observation.index' : 'hospitalisation.index')
            ->with('success', "{$patient->full_name} admis(e) en {$unit} — {$room->number}, lit {$bedNumber}.");
    }

    /**
     * Étape 1 de la sortie : la sortie est planifiée. Le patient reste
     * hospitalisé, la facturation continue, et le dossier reste ouvert.
     */
    public function planDischarge(Admission $admission): RedirectResponse
    {
        if ($admission->discharged_at) {
            return back()->with('error', 'Ce patient a déjà quitté l\'unité.');
        }

        $admission->planDischarge();

        $admission->visit && Invoice::forVisit($admission->visit);

        return back()->with('success', "Sortie planifiée pour {$admission->patient->full_name}.");
    }

    public function cancelPlannedDischarge(Admission $admission): RedirectResponse
    {
        $admission->cancelPlannedDischarge();

        $admission->visit && Invoice::forVisit($admission->visit);

        return back()->with('success', "Sortie annulée — {$admission->patient->full_name} reste hospitalisé(e).");
    }

    /**
     * Étape 2 : sortie effective de l'unité.
     *
     * Cette action est la sortie *médicale*. Elle ne clôture pas le dossier :
     * c'est SortieController::close() qui l'exige, et qui refuse de le faire
     * tant que la facture n'est pas encaissée (invariant 4).
     */
    public function discharge(Admission $admission): RedirectResponse
    {
        if ($admission->discharged_at) {
            return back()->with('error', 'Cette sortie a déjà été enregistrée.');
        }

        $isObservation = $admission->unit() === Admission::STATUS_OBSERVATION;
        $patientName = $admission->patient->full_name;

        DB::transaction(function () use ($admission) {
            $admission->discharge();

            if ($admission->visit) {
                Invoice::forVisit($admission->visit);
            }
        });

        $message = $isObservation
            ? "Sortie d'observation enregistrée pour {$patientName} — le patient retourne en consultation."
            : "Sortie d'hospitalisation enregistrée pour {$patientName} — la facturation est transmise à la caisse.";

        return redirect()
            ->route($isObservation ? 'observation.index' : 'hospitalisation.index')
            ->with('success', $message);
    }
}
