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
     * Une demande porte un ou plusieurs examens : le médecin les prescrit en
     * une seule fois.
     *
     * Aucun prix n'est reçu du client. Il est résolu côté serveur depuis
     * `lab_exams`, à partir de `lab_exam_id` : un tarif saisi au formulaire
     * serait une seconde source de vérité, capable de diverger du catalogue et
     * de facturer un montant arbitraire. Les examens hors catalogue ne sont donc
     * pas acceptés — ils seraient consommés au laboratoire sans jamais être
     * facturés.
     *
     * `visit_id` reste facultatif : le médecin le transmet depuis la fiche de
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.lab_exam_id' => ['required', 'integer', 'exists:lab_exams,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Sélectionnez au moins un examen à demander.',
            'items.min' => 'Sélectionnez au moins un examen à demander.',
            'items.*.lab_exam_id.exists' => 'Cet examen n\'existe pas au catalogue.',
        ];
    }
}