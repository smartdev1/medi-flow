<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Le ticket modérateur et le montant de base sont validés ici, et non
     * seulement calculés dans le navigateur : la caisse saisit une base et un
     * taux, et le serveur refuse les valeurs incohérentes.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'service' => ['required', 'string', 'max:255'],
            'base_amount' => ['required', 'integer', 'min:0'],
            'taux' => ['required', 'numeric', 'min:0', 'max:100'],
            'amount' => ['required', 'integer', 'min:0'],
            'method' => ['required', Rule::in(array_keys(config('clinic.payment_methods')))],
            'status' => ['required', Rule::in(array_values(config('clinic.payment_statuses')))],
        ];
    }

    /**
     * Le montant saisi doit correspondre au calcul du ticket modérateur :
     * la caisse ne peut pas encaisser un montant arbitraire.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $base = (int) $this->input('base_amount');
            $taux = (float) $this->input('taux');
            $amount = (int) $this->input('amount');

            $expected = (int) round($base * (1 - $taux / 100));

            if (abs($amount - $expected) > 1) {
                $validator->errors()->add(
                    'amount',
                    "Le montant à encaisser doit être de {$expected} FCFA (montant de base moins le ticket modérateur)."
                );
            }

            if ($this->input('status') === Payment::STATUS_PAID && $amount <= 0) {
                $validator->errors()->add('amount', 'Un encaissement soldé doit être supérieur à zéro.');
            }
        });
    }
}
