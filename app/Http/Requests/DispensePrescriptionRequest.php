<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispensePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'method' => ['required', 'in:Espèces,Carte bancaire,Mobile Money,Assurance CNAM,CMU'],
        ];
    }
}
