<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class SyncServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('syncServices', $this->route('employee'));
    }

    public function rules(): array
    {
        return [
            'service_ids' => ['present', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_ids.present' => 'O campo service_ids é obrigatório (pode ser um array vazio).',
            'service_ids.*.exists' => 'Um ou mais serviços informados não existem.',
        ];
    }
}
