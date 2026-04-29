<?php

namespace App\Http\Requests\Api\Debt;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $debtId = $this->route('debt');

        return [
            'farmer_id' => [
                'sometimes',
                'exists:farmers,id',
                'integer',
            ],
            'transaction_id' => [
                'sometimes',
                'exists:transactions,id',
                'integer',
            ],
            'original_amount_fcfa' => [
                'sometimes',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'paid_amount_fcfa' => [
                'sometimes',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'remaining_amount_fcfa' => [
                'sometimes',
                'numeric',
                'min:0',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['pending', 'paid', 'cancelled']),
            ],
            'incurred_at' => [
                'sometimes',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'farmer_id.exists' => 'L\'agriculteur spécifié n\'existe pas.',
            'farmer_id.integer' => 'L\'identifiant de l\'agriculteur doit être un entier.',
            
            'transaction_id.exists' => 'La transaction spécifiée n\'existe pas.',
            'transaction_id.integer' => 'L\'identifiant de la transaction doit être un entier.',
            
            'original_amount_fcfa.numeric' => 'Le montant original doit être un nombre.',
            'original_amount_fcfa.min' => 'Le montant original ne peut pas être négatif.',
            'original_amount_fcfa.regex' => 'Le montant original doit avoir au maximum 2 décimales.',
            
            'paid_amount_fcfa.numeric' => 'Le montant payé doit être un nombre.',
            'paid_amount_fcfa.min' => 'Le montant payé ne peut pas être négatif.',
            'paid_amount_fcfa.regex' => 'Le montant payé doit avoir au maximum 2 décimales.',
            
            'remaining_amount_fcfa.numeric' => 'Le montant restant doit être un nombre.',
            'remaining_amount_fcfa.min' => 'Le montant restant ne peut pas être négatif.',
            'remaining_amount_fcfa.regex' => 'Le montant restant doit avoir au maximum 2 décimales.',
            
            'status.in' => 'Le statut spécifié n\'est pas valide. Les valeurs autorisées sont : pending, paid, cancelled.',
            
            'incurred_at.date' => 'La date d\'échéance doit être une date valide.',
            'incurred_at.before_or_equal' => 'La date d\'échéance ne peut pas être dans le futur.',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'incurred_at' => $this->input('incurred_at') 
                ? date('Y-m-d H:i:s', strtotime($this->input('incurred_at')))
                : null,
        ]);
    }
}
