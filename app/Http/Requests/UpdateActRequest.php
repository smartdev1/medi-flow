<?php

namespace App\Http\Requests;

use App\Enums\ActCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateActRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:acts,code,'.$this->route('act')->id],
            'label' => ['required', 'string', 'max:255'],
            'category' => ['required', new Enum(ActCategory::class)],
            'base_price' => ['required', 'integer', 'min:0'],
            'insured_price' => ['nullable', 'integer', 'min:0'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'in:accueil,caissier,medecin,pediatre,laborantin,pharmacien,logistique,docteur_general,admin'],
        ];
    }
}
