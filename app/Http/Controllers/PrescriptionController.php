<?php

namespace App\Http\Controllers;

use App\Http\Requests\DispensePrescriptionRequest;
use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PrescriptionController extends Controller
{
    public function store(StorePrescriptionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);

        $prescription = $patient->prescriptions()->create([
            'prescribed_by' => Auth::id(),
            'stock_status' => 'Disponible',
            'status' => Prescription::STATUS_AWAITING_DISPENSE,
            'requested_at' => Carbon::parse($data['date']),
        ]);

        $shortage = false;

        foreach ($data['items'] as $item) {
            $medication = Medication::findOrFail($item['medication_id']);

            if ($item['quantity'] > $medication->stock_quantity) {
                $shortage = true;
            }

            $prescription->items()->create([
                'medication_id' => $medication->id,
                'name' => $medication->name,
                'quantity' => $item['quantity'],
                'unit_price' => $medication->unit_price,
            ]);
        }

        if ($shortage) {
            $prescription->update(['stock_status' => 'Rupture partielle']);
        }

        return redirect()
            ->back()
            ->with('success', "Ordonnance enregistrée pour {$patient->full_name}.");
    }

    public function dispense(DispensePrescriptionRequest $request, Prescription $prescription): RedirectResponse
    {
        if ($prescription->status === Prescription::STATUS_DISPENSED) {
            return redirect()->route('pharmacie.index')->with('success', 'Cette ordonnance a déjà été délivrée.');
        }

        $prescription->loadMissing('items.medication', 'patient');

        if (! $prescription->hasSufficientStock()) {
            return redirect()
                ->route('pharmacie.index')
                ->with('error', "Stock insuffisant pour délivrer l'ordonnance de {$prescription->patient->full_name} — demandez un réapprovisionnement à la logistique.");
        }

        $total = $prescription->dispense(Auth::user());

        Payment::create([
            'patient_id' => $prescription->patient_id,
            'visit_id' => $prescription->patient->visits()->latest('arrived_at')->value('id'),
            'prescription_id' => $prescription->id,
            'service' => 'Pharmacie — '.$prescription->medications_summary,
            'amount' => $total,
            'method' => $request->validated('method'),
            'status' => 'Payé',
        ]);

        return redirect()
            ->route('pharmacie.index')
            ->with('success', "Ordonnance de {$prescription->patient->full_name} délivrée et encaissée (".number_format($total, 0, ',', ' ')." FCFA).");
    }
}
