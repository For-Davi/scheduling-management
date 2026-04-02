<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('companies')->ignore($this->user()->company_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da empresa é obrigatório.',
            'slug.required' => 'O slug é obrigatório.',
            'slug.regex'    => 'O slug deve conter apenas letras minúsculas, números e hífens.',
            'slug.unique'   => 'Este slug já está em uso por outra empresa.',
        ];
    }
}
