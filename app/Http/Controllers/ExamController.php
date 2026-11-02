<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\LabExam;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        // Le prix n'est pas transmis par le client : il provient du catalogue,
        // qui reste la seule source de tarif. Le libellé retenu est celui du
        // catalogue également, afin qu'un examen demandé et un examen facturé
        // portent toujours le même nom.
        $catalog = LabExam::whereIn('id', array_column($data['items'], 'lab_exam_id'))
            ->get()
            ->keyBy('id');

        foreach ($data['items'] as $item) {
            $labExam = $catalog->get($item['lab_exam_id']);

            abort_if($labExam === null, 422, 'Cet examen n\'existe pas au catalogue.');

            $patient->exams()->create([
                'visit_id' => $visit?->id,
                'name' => $labExam->name,
                'price' => $labExam->price,
                'status' => Exam::STATUS_AWAITING_SAMPLE,
                'requested_at' => now(),
            ]);
        }

        // Un examen est une prestation payable : la facture du dossier concerné
        // est actualisée, sinon la demande resterait invisible à la caisse.
        $visit ??= $patient->visits()->active()->latest('arrived_at')->first();

        if ($visit) {
            Invoice::forVisit($visit);
        }

        $count = count($data['items']);
        $label = $count === 1 ? '1 examen demandé' : $count.' examens demandés';

        return redirect()
            ->back()
            ->with('success', "{$label} pour {$patient->full_name}.");
    }

    public function advance(Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);
        $exam->advance();

        return redirect()
            ->route('laboratoire.index')
            ->with('success', "Examen de {$exam->patient->full_name} mis à jour — {$exam->status}.");
    }

    /**
     * Recherche dans le catalogue des examens de laboratoire.
     *
     * Le catalogue est un tarif de référence : le médecin compose son libellé
     * au fur et à mesure, et la recherche est donc interrogée en base plutôt
     * que servie depuis une liste injectée dans la page.
     *
     * Le prix n'est pas renvoyé. Il n'est ni saisi ni affiché par le médecin,
     * et le serveur le résout seul au moment de la demande : l'envoyer ici
     * exposerait une donnée que le prescripteur n'a pas à connaître.
     */
    public function search(Request $request): JsonResponse
    {
        $exams = LabExam::query()
            ->searching($request->string('q')->toString())
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'category']);

        return response()->json($exams);
    }
}
