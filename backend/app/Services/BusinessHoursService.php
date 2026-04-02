<?php

namespace App\Services;

use App\Models\BlockedDay;
use App\Models\BusinessHour;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class BusinessHoursService
{
    /**
     * Retorna as configurações de horário da empresa (7 dias) + buffer.
     * Cria os registros padrão caso ainda não existam.
     */
    public function getBusinessHours(Company $company): array
    {
        $this->ensureDefaultsExist($company);

        $days = BusinessHour::where('company_id', $company->id)
            ->orderBy('day_of_week')
            ->get(['id', 'day_of_week', 'open_time', 'close_time', 'is_open']);

        return [
            'buffer_time_minutes' => $company->buffer_time_minutes,
            'days' => $days,
        ];
    }

    /**
     * Atualiza os 7 dias e o buffer global da empresa.
     */
    public function updateBusinessHours(Company $company, array $data): array
    {
        $company->update(['buffer_time_minutes' => $data['buffer_time_minutes']]);

        foreach ($data['days'] as $day) {
            BusinessHour::updateOrCreate(
                [
                    'company_id'  => $company->id,
                    'day_of_week' => $day['day_of_week'],
                ],
                [
                    'open_time'  => $day['is_open'] ? ($day['open_time'] ?? null) : null,
                    'close_time' => $day['is_open'] ? ($day['close_time'] ?? null) : null,
                    'is_open'    => $day['is_open'],
                ]
            );
        }

        return $this->getBusinessHours($company);
    }

    /**
     * Lista os dias bloqueados.
     * Admin: todos da empresa. Funcionário: apenas os seus.
     */
    public function getBlockedDays(Company $company, User $user): Collection
    {
        $query = BlockedDay::where('company_id', $company->id)->orderBy('date');

        if ($user->isEmployee()) {
            $employee = $this->resolveEmployee($user, $company);
            $query->where('employee_id', $employee->id);
        }

        return $query->with('employee:id,name')->get();
    }

    /**
     * Cria um bloqueio de dia.
     * Admin: pode bloquear para a empresa inteira (employee_id nulo) ou para um funcionário específico.
     * Funcionário: bloqueia apenas seu próprio dia.
     */
    public function storeBlockedDay(Company $company, User $user, array $data): BlockedDay
    {
        if ($user->isEmployee()) {
            $employee = $this->resolveEmployee($user, $company);
            $data['employee_id'] = $employee->id;
        } elseif (! empty($data['employee_id'])) {
            // Admin: garante que o funcionário pertence à empresa
            Employee::where('id', $data['employee_id'])
                ->where('company_id', $company->id)
                ->firstOrFail();
        }

        return BlockedDay::create([
            'company_id'  => $company->id,
            'employee_id' => $data['employee_id'] ?? null,
            'date'        => $data['date'],
            'reason'      => $data['reason'] ?? null,
        ]);
    }

    /**
     * Remove um bloqueio de dia.
     * Funcionário só pode remover os seus próprios bloqueios.
     */
    public function destroyBlockedDay(Company $company, User $user, BlockedDay $blockedDay): void
    {
        abort_if($blockedDay->company_id !== $company->id, 403, 'Acesso negado.');

        if ($user->isEmployee()) {
            $employee = $this->resolveEmployee($user, $company);
            abort_if(
                $blockedDay->employee_id !== $employee->id,
                403,
                'Funcionário só pode remover seus próprios bloqueios.'
            );
        }

        $blockedDay->delete();
    }

    // -------------------------------------------------------------------------

    /**
     * Cria os 7 registros padrão caso a empresa não tenha nenhum configurado.
     * Segunda a sexta: 09:00–18:00, aberto. Sábado/domingo: fechado.
     */
    private function ensureDefaultsExist(Company $company): void
    {
        if (BusinessHour::where('company_id', $company->id)->exists()) {
            return;
        }

        for ($day = 0; $day <= 6; $day++) {
            $isWeekday = $day >= 1 && $day <= 5;

            BusinessHour::create([
                'company_id'  => $company->id,
                'day_of_week' => $day,
                'open_time'   => $isWeekday ? '09:00' : null,
                'close_time'  => $isWeekday ? '18:00' : null,
                'is_open'     => $isWeekday,
            ]);
        }
    }

    /**
     * Resolve o Employee vinculado ao User autenticado dentro da empresa.
     */
    private function resolveEmployee(User $user, Company $company): Employee
    {
        return Employee::where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->firstOrFail();
    }
}
