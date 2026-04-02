<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // rota pública
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'service_id'  => ['required', 'integer', 'exists:services,id'],
            'date'        => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required'    => 'O funcionário é obrigatório.',
            'employee_id.exists'      => 'Funcionário não encontrado.',
            'service_id.required'     => 'O serviço é obrigatório.',
            'service_id.exists'       => 'Serviço não encontrado.',
            'date.required'           => 'A data é obrigatória.',
            'date.date_format'        => 'A data deve estar no formato AAAA-MM-DD.',
            'date.after_or_equal'     => 'Não é possível consultar datas passadas.',
        ];
    }
}
