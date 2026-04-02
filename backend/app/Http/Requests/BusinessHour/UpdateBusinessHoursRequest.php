<?php

namespace App\Http\Requests\BusinessHour;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // autorização via middleware role:admin na rota
    }

    public function rules(): array
    {
        return [
            'buffer_time_minutes'          => ['required', 'integer', 'min:0', 'max:120'],
            'days'                         => ['required', 'array', 'size:7'],
            'days.*.day_of_week'           => ['required', 'integer', 'min:0', 'max:6'],
            'days.*.is_open'               => ['required', 'boolean'],
            'days.*.open_time'             => ['nullable', 'required_if:days.*.is_open,true', 'date_format:H:i'],
            'days.*.close_time'            => ['nullable', 'required_if:days.*.is_open,true', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.size'                         => 'Envie exatamente 7 dias (0 = domingo a 6 = sábado).',
            'days.*.day_of_week.required'       => 'O campo day_of_week é obrigatório.',
            'days.*.day_of_week.min'            => 'day_of_week deve ser entre 0 (domingo) e 6 (sábado).',
            'days.*.day_of_week.max'            => 'day_of_week deve ser entre 0 (domingo) e 6 (sábado).',
            'days.*.open_time.required_if'      => 'O horário de abertura é obrigatório quando o dia está aberto.',
            'days.*.close_time.required_if'     => 'O horário de fechamento é obrigatório quando o dia está aberto.',
            'days.*.open_time.date_format'      => 'Horário de abertura deve estar no formato HH:MM (ex: 09:00).',
            'days.*.close_time.date_format'     => 'Horário de fechamento deve estar no formato HH:MM (ex: 18:00).',
            'buffer_time_minutes.max'           => 'O buffer máximo permitido é 120 minutos.',
        ];
    }
}
