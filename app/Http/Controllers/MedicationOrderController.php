<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicationOrderRequest;
use App\Models\MedicationOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MedicationOrderController extends Controller
{
    public function store(StoreMedicationOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $order = MedicationOrder::create([
            'reference' => MedicationOrder::generateReference(),
            'requested_by' => Auth::id(),
            'status' => MedicationOrder::STATUS_PENDING,
            'ordered_at' => now(),
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $order->items()->create([
                'medication_id' => $item['medication_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        return redirect()
            ->route('logistique.index')
            ->with('success', "Commande {$order->reference} créée — pensez à l'imprimer pour le fournisseur.");
    }

    public function receive(MedicationOrder $medicationOrder): RedirectResponse
    {
        if ($medicationOrder->status === MedicationOrder::STATUS_RECEIVED) {
            return redirect()
                ->route('logistique.index')
                ->with('success', 'Cette commande a déjà été réceptionnée.');
        }

        $medicationOrder->markReceived();

        return redirect()
            ->route('logistique.index')
            ->with('success', "Commande {$medicationOrder->reference} réceptionnée — stock mis à jour.");
    }

    public function print(MedicationOrder $medicationOrder): View
    {
        $medicationOrder->load('items.medication', 'requestedBy');

        return view('logistique.order-print', ['order' => $medicationOrder]);
    }
}
