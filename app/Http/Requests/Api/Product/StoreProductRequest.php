<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'price_fcfa' => ['required', 'numeric', 'between:0,9999999999.99'],
            'unit' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_name.required' => 'Le nom du produit est requis.',
            'product_name.string' => 'Le nom du produit doit être une chaîne de caractères.',
            'product_name.max' => 'Le nom du produit ne peut pas dépasser 255 caractères.',
            'slug.required' => 'Le slug est requis.',
            'slug.string' => 'Le slug doit être une chaîne de caractères.',
            'slug.max' => 'Le slug ne peut pas dépasser 255 caractères.',
            'slug.unique' => 'Ce slug est déjà utilisé.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'category_id.required' => 'La catégorie est requise.',
            'category_id.exists' => 'La catégorie spécifiée n\'existe pas.',
            'price_fcfa.required' => 'Le prix est requis.',
            'price_fcfa.numeric' => 'Le prix doit être un nombre.',
            'price_fcfa.between' => 'Le prix doit être compris entre 0 et 9,999,999,999.99 FCFA.',
            'unit.required' => 'L\'unité est requise.',
            'unit.string' => 'L\'unité doit être une chaîne de caractères.',
            'unit.max' => 'L\'unité ne peut pas dépasser 50 caractères.',
            'is_active.boolean' => 'Le champ is_active doit être un booléen.',
        ];
    }
}
