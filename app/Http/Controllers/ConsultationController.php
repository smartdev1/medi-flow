<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Consultation;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;

class ConsultationController extends Controller
{
    /**
     * Fiche de consultation : constantes, notes et acte réalisé.
     *
     * L'acte était jusqu'ici choisi à l'accueil, avant que le patient ne soit
     * vu, et souvent laissé vide. La consultation revenait alors à 0 FCFA sans
     * que rien ne le signale. C'est le médecin qui sait quel acte a été
     * réalisé : le sélecteur vit donc ici, et la facture est recalculée à
     * chaque changement.
     */
    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();

        $payload = ['notes' => $data['notes'] ?? null];

        // 'vitals' n'est plus transmis par le formulaire du médecin. Sans cette
        // garde, l'enregistrement des notes écraserait les constantes relevées à
        // l'accueil par un tableau vide.
        if (array_key_exists('vitals', $data)) {
            $payload['vitals'] = collect($data['vitals'])
                ->map(fn (?string $value, string $label) => ['label' => $label, 'value' => $value ?: '—'])
                ->values()
                ->all();
        }

        $consultation->update($payload);

        if (array_key_exists('act_id', $data)) {
            $visit = $consultation->visit;

            if ($visit && $visit->act_id != $data['act_id']) {
                $visit->update(['act_id' => $data['act_id']]);

                // Le dossier doit refléter l'acte réellement réalisé, sinon la
                // caisse encaisse une consultation à 0 FCFA.
                Invoice::forVisit($visit);
            }
        }

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
