<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Vente de médicaments au point de vente pharmacie.
 *
 * Aucun prix n'est reçu du client : il est résolu côté serveur depuis
 * `medications`, à partir de `medication_id`. Un tarif saisi au formulaire
 * serait une seconde source de vérité, capable de facturer un montant
 * arbitraire inférieur au coût réel. Les médicaments hors catalogue ne sont
 * donc pas acceptés.
 *
 * `patient_id` est obligatoire même lorsque la vente ne se rattache à aucune
 * consultation : tout patient est déjà enregistré, et c'est ce dossier qui
 * portera la vente.
 */
class StorePharmacySaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'method' => ['required', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'integer', 'exists:medications,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Ajoutez au moins un médicament à la vente.',
            'items.min' => 'Ajoutez au moins un médicament à la vente.',
            'items.*.medication_id.exists' => 'Ce médicament n\'existe pas au catalogue.',
            'items.*.quantity.min' => 'La quantité vendue doit être supérieure à zéro.',
        ];
    }
}
