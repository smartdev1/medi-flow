<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;

class ConsultationController extends Controller
{
    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();

        $consultation->update([
            'vitals' => collect($data['vitals'] ?? [])
                ->map(fn (?string $value, string $label) => ['label' => $label, 'value' => $value ?: '—'])
                ->values()
                ->all(),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()
            ->route('medecin.index', ['patient' => $consultation->patient_id])
            ->with('success', 'Fiche de consultation enregistrée.');
    }

    public function close(Consultation $consultation): RedirectResponse
    {
        $consultation->close();

        return redirect()
            ->route('medecin.index')
            ->with('success', "Consultation de {$consultation->patient->full_name} clôturée.");
    }
}
