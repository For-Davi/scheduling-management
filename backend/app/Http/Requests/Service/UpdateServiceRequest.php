<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('service'));
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:480'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.numeric' => 'O preço deve ser um número.',
            'price.min' => 'O preço não pode ser negativo.',
            'duration_minutes.integer' => 'A duração deve ser um número inteiro de minutos.',
            'duration_minutes.min' => 'A duração mínima é de 5 minutos.',
            'duration_minutes.max' => 'A duração máxima é de 480 minutos (8 horas).',
        ];
    }
}
