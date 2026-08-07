<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
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
            'service' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'method' => ['required', 'in:Espèces,Carte bancaire,Mobile Money,Assurance CNAM,CMU'],
            'status' => ['required', 'in:Payé,Partiellement payé,En attente'],
            'cashier_id' => ['required', 'exists:users,id'],
        ];
    }
}
