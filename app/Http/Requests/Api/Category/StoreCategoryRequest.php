<?php

namespace App\Http\Requests\Api\Category;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_name.required' => 'Le nom de la catégorie est requis.',
            'category_name.string' => 'Le nom doit être une chaîne de caractères.',
            'category_name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'slug.required' => 'Le slug est requis.',
            'slug.string' => 'Le slug doit être une chaîne de caractères.',
            'slug.max' => 'Le slug ne peut pas dépasser 255 caractères.',
            'slug.unique' => 'Ce slug est déjà utilisé.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'parent_id.exists' => 'La catégorie parente spécifiée n\'existe pas.',
            'is_active.boolean' => 'Le champ is_active doit être un booléen.',
        ];
    }
}
