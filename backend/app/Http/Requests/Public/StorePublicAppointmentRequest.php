<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // rota pública
    }

    public function rules(): array
    {
        return [
            'employee_id'  => ['required', 'integer', 'exists:employees,id'],
            'service_id'   => ['required', 'integer', 'exists:services,id'],
            'starts_at'    => ['required', 'date_format:Y-m-d H:i'],
            'client_name'  => ['required', 'string', 'max:150'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:20'],
            'lgpd_consent' => ['required', 'boolean', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.date_format'  => 'O horário deve estar no formato AAAA-MM-DD HH:MM.',
            'lgpd_consent.accepted'  => 'Você deve aceitar os termos da LGPD para continuar.',
            'client_name.required'   => 'O nome é obrigatório.',
            'client_email.required'  => 'O e-mail é obrigatório.',
            'client_email.email'     => 'Informe um e-mail válido.',
            'employee_id.exists'     => 'Funcionário não encontrado.',
            'service_id.exists'      => 'Serviço não encontrado.',
        ];
    }
}
