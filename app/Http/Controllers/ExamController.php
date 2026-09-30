<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;

class ExamController extends Controller
{
    public function store(StoreExamRequest $request): RedirectResponse
    {
        $this->authorize('create', Exam::class);
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);

        // L'examen est la conséquence d'une consultation : il est rattaché à la
        // visite qui l'a demandé. Une demande saisie au laboratoire, hors
        // consultation, reste sans rattachement et sera imputée à la venue en
        // cours.
        $visit = null;

        if (! empty($data['visit_id'])) {
            $visit = Visit::findOrFail($data['visit_id']);

            abort_unless(
                $visit->patient_id === $patient->id,
                422,
                'La visite sélectionnée ne correspond pas au patient choisi.'
            );
        }

        $patient->exams()->create([
            'visit_id' => $visit?->id,
            'name' => $data['name'],
            'price' => (int) ($data['price'] ?? 0),
            'status' => Exam::STATUS_AWAITING_SAMPLE,
            'requested_at' => now(),
        ]);

        // Un examen est une prestation payable : la facture du dossier concerné
        // est actualisée, sinon la demande resterait invisible à la caisse.
        $visit ??= $patient->visits()->active()->latest('arrived_at')->first();

        if ($visit) {
            Invoice::forVisit($visit);
        }

        return redirect()
            ->back()
            ->with('success', "Examen « {$data['name']} » demandé pour {$patient->full_name}.");
    }

    public function advance(Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);
        $exam->advance();

        return redirect()
            ->route('laboratoire.index')
            ->with('success', "Examen de {$exam->patient->full_name} mis à jour — {$exam->status}.");
    }
}
