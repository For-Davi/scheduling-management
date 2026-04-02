<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BlockedDay;
use App\Models\BusinessHour;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Service;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Retorna os slots disponíveis para um funcionário + serviço em uma data.
     *
     * @return array{ date: string, employee_id: int, service_id: int, slots: array<int, array{starts_at: string, ends_at: string}> }
     */
    public function getAvailableSlots(
        Company $company,
        Employee $employee,
        Service $service,
        string $date
    ): array {
        $result = [
            'date'        => $date,
            'employee_id' => $employee->id,
            'service_id'  => $service->id,
            'slots'       => [],
        ];

        // 1. Verificar horário de funcionamento para o dia da semana
        $dayOfWeek = Carbon::parse($date)->dayOfWeek; // 0 = domingo, 6 = sábado
        $businessHour = BusinessHour::where('company_id', $company->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $businessHour || ! $businessHour->is_open) {
            return $result;
        }

        // 2. Verificar se o dia está bloqueado (empresa ou funcionário)
        $isBlocked = BlockedDay::where('company_id', $company->id)
            ->where('date', $date)
            ->where(function ($query) use ($employee) {
                // Bloqueio da empresa inteira (employee_id nulo) OU bloqueio pessoal do funcionário
                $query->whereNull('employee_id')
                    ->orWhere('employee_id', $employee->id);
            })
            ->exists();

        if ($isBlocked) {
            return $result;
        }

        // 3. Gerar todos os slots possíveis dentro do horário de funcionamento
        $slots = $this->generateSlots($businessHour, $service->duration_minutes, $company->buffer_time_minutes, $date);

        // 4. Remover slots com menos de 2 horas de antecedência (Regra de Negócio 1)
        $minimumStartAt = Carbon::now()->addHours(2);
        $slots = array_values(array_filter($slots, function (array $slot) use ($minimumStartAt, $date) {
            return Carbon::parse($date . ' ' . $slot['starts_at'])->gte($minimumStartAt);
        }));

        // 5. Remover slots que colidem com agendamentos já existentes
        $slots = $this->filterBusySlots($slots, $employee, $service->duration_minutes, $company->buffer_time_minutes, $date);

        $result['slots'] = $slots;

        return $result;
    }

    // -------------------------------------------------------------------------

    /**
     * Gera todos os slots possíveis entre open_time e close_time.
     *
     * O passo entre slots = duration_minutes + buffer_time_minutes.
     * O último slot deve terminar (starts_at + duration_minutes) <= close_time.
     *
     * @return array<int, array{starts_at: string, ends_at: string}>
     */
    private function generateSlots(
        BusinessHour $businessHour,
        int $durationMinutes,
        int $bufferMinutes,
        string $date
    ): array {
        $step = $durationMinutes + $bufferMinutes;
        $current = Carbon::parse($date . ' ' . $businessHour->open_time);
        $closeTime = Carbon::parse($date . ' ' . $businessHour->close_time);

        $slots = [];

        while (true) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);

            // O slot só é válido se terminar dentro (ou exatamente em) close_time
            if ($slotEnd->gt($closeTime)) {
                break;
            }

            $slots[] = [
                'starts_at' => $current->format('H:i'),
                'ends_at'   => $slotEnd->format('H:i'),
            ];

            $current->addMinutes($step);
        }

        return $slots;
    }

    /**
     * Remove slots que colidem com agendamentos já confirmados (status = scheduled).
     *
     * Regra de sobreposição: um slot [S_start, S_end] colide com um agendamento existente
     * [A_start, A_end] quando:
     *   S_start < (A_end + buffer) AND S_end > A_start
     *
     * O buffer é aplicado ao fim do agendamento existente para garantir o intervalo mínimo
     * entre atendimentos.
     *
     * @param  array<int, array{starts_at: string, ends_at: string}>  $slots
     * @return array<int, array{starts_at: string, ends_at: string}>
     */
    private function filterBusySlots(
        array $slots,
        Employee $employee,
        int $durationMinutes,
        int $bufferMinutes,
        string $date
    ): array {
        $appointments = Appointment::where('employee_id', $employee->id)
            ->where('status', 'scheduled')
            ->whereDate('starts_at', $date)
            ->get(['starts_at', 'ends_at']);

        if ($appointments->isEmpty()) {
            return $slots;
        }

        return array_values(array_filter($slots, function (array $slot) use ($appointments, $bufferMinutes, $date) {
            $slotStart = Carbon::parse($date . ' ' . $slot['starts_at']);
            $slotEnd = Carbon::parse($date . ' ' . $slot['ends_at']);

            foreach ($appointments as $appt) {
                $apptEffectiveEnd = $appt->ends_at->copy()->addMinutes($bufferMinutes);

                if ($slotStart->lt($apptEffectiveEnd) && $slotEnd->gt($appt->starts_at)) {
                    return false;
                }
            }

            return true;
        }));
    }
}
