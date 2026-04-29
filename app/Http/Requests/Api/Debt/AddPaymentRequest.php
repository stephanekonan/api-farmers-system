<?php

namespace App\Http\Requests\Api\Debt;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'repayment_id' => [
                'required',
                'exists:repayments,id',
                'integer',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'repayment_id.required' => 'L\'identifiant du remboursement est requis.',
            'repayment_id.exists' => 'Le remboursement spécifié n\'existe pas.',
            'repayment_id.integer' => 'L\'identifiant du remboursement doit être un entier.',
            
            'amount.required' => 'Le montant du paiement est requis.',
            'amount.numeric' => 'Le montant du paiement doit être un nombre.',
            'amount.min' => 'Le montant du paiement doit être supérieur à 0.',
            'amount.regex' => 'Le montant du paiement doit avoir au maximum 2 décimales.',
        ];
    }
}
