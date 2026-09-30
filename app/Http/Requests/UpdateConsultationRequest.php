<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsultationRequest extends FormRequest
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
     * `act_id` est facultatif : un acte peut être corrigé après coup, et une
     * consultation reste valide sans acte renseigné. Mais dès qu'il est
     * transmis, il doit exister et la facture du dossier est recalculée.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vitals' => ['nullable', 'array'],
            'vitals.*' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'act_id' => ['nullable', 'exists:acts,id'],
        ];
    }
}
