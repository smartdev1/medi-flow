<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * `price` reste facultatif pour ne pas bloquer la saisie au guichet, mais
     * il est conservé : un examen consommé sans prix ne serait jamais facturé,
     * et la facture de la visite perdrait une prestation réelle.
     *
     * `visit_id` est facultatif : le médecin le transmet depuis la fiche de
     * consultation, le laboratoire ne dispose d'aucun contexte de visite. Son
     * rattachement au patient est vérifié dans le contrôleur.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'visit_id' => ['nullable', 'exists:visits,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
