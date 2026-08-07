<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Models\Exam;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;

class ExamController extends Controller
{
    public function store(StoreExamRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);

        $patient->exams()->create([
            'name' => $data['name'],
            'status' => Exam::STATUS_AWAITING_SAMPLE,
            'requested_at' => now(),
        ]);

        return redirect()
            ->back()
            ->with('success', "Examen « {$data['name']} » demandé pour {$patient->full_name}.");
    }

    public function advance(Exam $exam): RedirectResponse
    {
        $exam->advance();

        return redirect()
            ->route('laboratoire.index')
            ->with('success', "Examen de {$exam->patient->full_name} mis à jour — {$exam->status}.");
    }
}
