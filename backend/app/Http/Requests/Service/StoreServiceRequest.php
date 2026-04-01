<?php

namespace App\Http\Requests\Service;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Service::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do serviço é obrigatório.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um número.',
            'price.min' => 'O preço não pode ser negativo.',
            'duration_minutes.required' => 'A duração é obrigatória.',
            'duration_minutes.integer' => 'A duração deve ser um número inteiro de minutos.',
            'duration_minutes.min' => 'A duração mínima é de 5 minutos.',
            'duration_minutes.max' => 'A duração máxima é de 480 minutos (8 horas).',
        ];
    }
}
