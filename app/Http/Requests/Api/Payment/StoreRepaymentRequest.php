<?php

namespace App\Http\Requests\Api\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farmer_id' => ['required', 'exists:farmers,id'],
            'commodity_kg' => ['required', 'numeric', 'min:0.001'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'farmer_id.required' => 'L\'identifiant de l\'agriculteur est requis.',
            'farmer_id.exists' => 'L\'agriculteur spécifié n\'existe pas.',
            'commodity_kg.required' => 'La quantité de marchandise est requise.',
            'commodity_kg.numeric' => 'La quantité doit être un nombre.',
            'commodity_kg.min' => 'La quantité doit être supérieure à 0.',
            'notes.string' => 'Les notes doivent être du texte.',
            'notes.max' => 'Les notes ne peuvent pas dépasser 500 caractères.',
        ];
    }
}
