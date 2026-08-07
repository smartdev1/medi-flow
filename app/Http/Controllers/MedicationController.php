<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustMedicationStockRequest;
use App\Http\Requests\StoreMedicationRequest;
use App\Models\Medication;
use Illuminate\Http\RedirectResponse;

class MedicationController extends Controller
{
    public function store(StoreMedicationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Medication::create($data);

        return redirect()
            ->route('logistique.index')
            ->with('success', "Médicament « {$data['name']} » ajouté au catalogue.");
    }

    public function adjustStock(AdjustMedicationStockRequest $request, Medication $medication): RedirectResponse
    {
        $quantity = $request->validated('quantity');

        $medication->increment('stock_quantity', $quantity);

        return redirect()
            ->route('logistique.index')
            ->with('success', "Stock de {$medication->name} augmenté de {$quantity} {$medication->unit}(s).");
    }
}
