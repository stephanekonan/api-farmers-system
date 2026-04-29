<?php

namespace App\Http\Requests\Api\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_identifier' => ['required', 'string', 'max:255', 'unique:farmers,card_identifier'],
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'unique:farmers,phone'],
            'village' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'credit_limit_fcfa' => ['sometimes', 'numeric', 'between:0,9999999999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'card_identifier.required' => 'L\'identifiant de la carte est requis.',
            'card_identifier.string' => 'L\'identifiant doit être une chaîne de caractères.',
            'card_identifier.max' => 'L\'identifiant ne peut pas dépasser 255 caractères.',
            'card_identifier.unique' => 'Cet identifiant de carte est déjà utilisé.',
            'firstname.required' => 'Le prénom est requis.',
            'firstname.string' => 'Le prénom doit être une chaîne de caractères.',
            'firstname.max' => 'Le prénom ne peut pas dépasser 255 caractères.',
            'lastname.required' => 'Le nom de famille est requis.',
            'lastname.string' => 'Le nom de famille doit être une chaîne de caractères.',
            'lastname.max' => 'Le nom de famille ne peut pas dépasser 255 caractères.',
            'phone.required' => 'Le numéro de téléphone est requis.',
            'phone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'phone.max' => 'Le numéro de téléphone ne peut pas dépasser 255 caractères.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'village.string' => 'Le village doit être une chaîne de caractères.',
            'village.max' => 'Le village ne peut pas dépasser 255 caractères.',
            'region.string' => 'La région doit être une chaîne de caractères.',
            'region.max' => 'La région ne peut pas dépasser 255 caractères.',
            'credit_limit_fcfa.numeric' => 'La limite de crédit doit être un nombre.',
            'credit_limit_fcfa.between' => 'La limite de crédit doit être comprise entre 0 et 9,999,999,999.99 FCFA.',
        ];
    }
}
