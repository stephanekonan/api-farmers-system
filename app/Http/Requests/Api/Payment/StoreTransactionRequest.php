<?php

namespace App\Http\Requests\Api\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farmer_id' => ['required', 'exists:farmers,id'],
            'payment_method' => ['required', Rule::in(['cash', 'credit'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
        ];
    }

    public function messages(): array
    {
        return [
            'farmer_id.required' => 'L\'identifiant de l\'agriculteur est requis.',
            'farmer_id.exists' => 'L\'agriculteur spécifié n\'existe pas.',
            'payment_method.required' => 'La méthode de paiement est requise.',
            'payment_method.in' => 'La méthode de paiement doit être "cash" ou "credit".',
            'items.required' => 'Au moins un article est requis.',
            'items.array' => 'Les articles doivent être un tableau.',
            'items.min' => 'Au moins un article est requis.',
            'items.*.product_id.required' => 'L\'identifiant du produit est requis pour chaque article.',
            'items.*.product_id.exists' => 'Le produit spécifié n\'existe pas.',
            'items.*.quantity.required' => 'La quantité est requise pour chaque article.',
            'items.*.quantity.numeric' => 'La quantité doit être un nombre.',
            'items.*.quantity.min' => 'La quantité doit être supérieure à 0.',
        ];
    }
}
