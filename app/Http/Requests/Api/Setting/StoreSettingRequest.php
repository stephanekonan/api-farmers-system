<?php

namespace App\Http\Requests\Api\Setting;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255', 'unique:settings,key'],
            'value' => ['required', 'numeric', 'between:0,999999.9999'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'La clé est requise.',
            'key.unique' => 'Cette clé existe déjà.',
            'value.required' => 'La valeur est requise.',
            'value.numeric' => 'La valeur doit être un nombre.',
            'value.between' => 'La valeur doit être comprise entre 0 et 999999.9999.',
            'description.max' => 'La description ne peut pas dépasser 500 caractères.',
        ];
    }
}
