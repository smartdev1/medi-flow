<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePrescriptionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            // Renseigné par la consultation qui prescrive. Facultatif : une
            // ordonnance saisie au guichet, hors consultation, n'a pas de
            // rattachement et reste imputée à la venue en cours.
            'visit_id' => ['nullable', 'exists:visits,id'],
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medication_id' => ['required', 'exists:medications,id'],
            'items.*.posology' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
