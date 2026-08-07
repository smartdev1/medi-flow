<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdmissionRequest;
use App\Models\Admission;
use App\Models\Patient;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AdmissionController extends Controller
{
    public function store(StoreAdmissionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);
        $room = Room::findOrFail($data['room_id']);

        $bedNumber = $room->available_bed_numbers[0] ?? null;

        if ($room->maintenance || $bedNumber === null) {
            throw ValidationException::withMessages([
                'room_id' => "La {$room->number} n'a plus de lit disponible.",
            ]);
        }

        $patient->admissions()->create([
            'room_id' => $room->id,
            'bed_number' => $bedNumber,
            'reason' => $data['reason'],
            'attending' => $data['attending'] ?? null,
            'status' => Admission::STATUS_OBSERVATION,
            'admitted_at' => now(),
        ]);

        return redirect()
            ->route('hospitalisation.index')
            ->with('success', "{$patient->full_name} admis(e) — {$room->number}, lit {$bedNumber}.");
    }

    public function discharge(Admission $admission): RedirectResponse
    {
        $admission->discharge();

        return redirect()
            ->route('hospitalisation.index')
            ->with('success', "Sortie de {$admission->patient->full_name} enregistrée.");
    }
}
