<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionRequest extends FormRequest
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
            'room_id' => ['required', 'exists:rooms,id'],
            'bed_number' => ['nullable', 'integer', 'min:1'],
            'type' => ['nullable', 'in:hospitalisation,observation'],
            'reason' => ['required', 'string', 'max:255'],
            'attending' => ['nullable', 'string', 'max:255'],
        ];
    }
}
