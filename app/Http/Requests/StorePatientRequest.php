<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
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
            'existing_patient_id' => ['nullable', 'exists:patients,id'],
            'first_name' => ['required_without:existing_patient_id', 'string', 'max:255'],
            'last_name' => ['required_without:existing_patient_id', 'string', 'max:255'],
            'sex' => ['required_without:existing_patient_id', 'in:M,F'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'coverage' => ['required_without:existing_patient_id', 'string', 'max:255'],
            'insurance_company_id' => ['nullable', 'exists:insurance_companies,id'],
            // Le ticket modérateur porte sur la consultation, pas sur le dossier :
            // deux venues du même patient peuvent relever de deux taux distincts.
            'ticket_moderateur' => [
                Rule::requiredIf(fn () => $this->input('coverage') === 'Assuré'),
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'surgery_type' => ['nullable', 'in:interne,externe'],
            'act_id' => ['nullable', 'exists:acts,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'urgent' => ['nullable', 'boolean'],
        ];
    }
}
