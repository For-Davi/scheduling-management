<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Service;
use Carbon\Carbon;

class BookingService
{
    /**
     * Cria um agendamento público aplicando as regras de negócio:
     *  - Regra 1: antecedência mínima de 2 horas
     *  - Regra 2: funcionário deve ter aptidão para o serviço
     *  - Regra 4: sem conflito de horários (considerando buffer)
     *  - Regra 8: log inicial em appointment_logs
     */
    public function storeAppointment(Company $company, array $data): Appointment
    {
        // Garante que o funcionário pertence à empresa e está ativo
        $employee = Employee::where('id', $data['employee_id'])
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Garante que o serviço pertence à empresa e está ativo
        $service = Service::where('id', $data['service_id'])
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Regra 2: aptidão obrigatória
        abort_unless(
            $employee->services()->where('service_id', $service->id)->exists(),
            422,
            'Este funcionário não realiza o serviço selecionado.'
        );

        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt   = $startsAt->copy()->addMinutes($service->duration_minutes);

        // Regra 1: antecedência mínima de 2 horas
        abort_if(
            $startsAt->lt(Carbon::now()->addHours(2)),
            422,
            'O agendamento deve ser feito com pelo menos 2 horas de antecedência.'
        );

        // Regra 4: verificar conflito de horários
        // O novo agendamento ocupa [starts_at, ends_at + buffer].
        // Há conflito se algum agendamento existente [A_start, A_end] satisfaz:
        //   A_start < ends_at + buffer  AND  A_end > starts_at
        $buffer      = $company->buffer_time_minutes;
        $effectiveEnd = $endsAt->copy()->addMinutes($buffer);

        $conflict = Appointment::where('employee_id', $employee->id)
            ->where('status', 'scheduled')
            ->where('starts_at', '<', $effectiveEnd)
            ->where('ends_at', '>', $startsAt)
            ->exists();

        abort_if($conflict, 409, 'Este horário não está mais disponível. Por favor, escolha outro.');

        // Criar agendamento
        $appointment = Appointment::create([
            'company_id'   => $company->id,
            'employee_id'  => $employee->id,
            'service_id'   => $service->id,
            'client_name'  => $data['client_name'],
            'client_email' => $data['client_email'],
            'client_phone' => $data['client_phone'] ?? null,
            'starts_at'    => $startsAt,
            'ends_at'      => $endsAt,
            'status'       => 'scheduled',
            'lgpd_consent' => $data['lgpd_consent'],
        ]);

        // Regra 8: log inicial (criação pelo cliente — user_id nulo)
        AppointmentLog::create([
            'appointment_id' => $appointment->id,
            'user_id'        => null,
            'old_status'     => null,
            'new_status'     => 'scheduled',
            'changed_at'     => now(),
        ]);

        return $appointment;
    }
}
