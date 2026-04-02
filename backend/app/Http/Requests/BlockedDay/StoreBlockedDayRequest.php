<?php

namespace App\Http\Requests\BlockedDay;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlockedDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorização feita no service (admin vs funcionário)
    }

    public function rules(): array
    {
        return [
            'date'        => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'reason'      => ['nullable', 'string', 'max:255'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'          => 'A data é obrigatória.',
            'date.date_format'       => 'A data deve estar no formato AAAA-MM-DD.',
            'date.after_or_equal'    => 'Não é possível bloquear datas passadas.',
            'employee_id.exists'     => 'Funcionário não encontrado.',
        ];
    }
}
