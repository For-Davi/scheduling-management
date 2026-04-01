<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * Lista todos os funcionários da empresa com seus serviços.
     *
     * @return Collection<int, Employee>
     */
    public function list(Company $company): Collection
    {
        return $company->employees()
            ->with('services:id,name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Cria um funcionário, aplicando a trava de limite do plano (regra de negócio #3).
     *
     * @throws HttpResponseException HTTP 403 se o limite foi atingido
     */
    public function store(Company $company, array $data): Employee
    {
        $activeCount = $company->employees()->where('is_active', true)->count();
        $limit = $company->planLimit('employees');

        if ($activeCount >= $limit) {
            throw new HttpResponseException(
                response()->json([
                    'message' => "Limite de funcionários do plano {$company->plan} atingido ({$limit}). Faça upgrade para adicionar mais.",
                ], 403)
            );
        }

        return $company->employees()->create($data);
    }

    /**
     * Atualiza os dados de um funcionário.
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->fresh(['services:id,name']);
    }

    /**
     * Remove um funcionário.
     *
     * @throws HttpResponseException HTTP 409 se houver agendamentos futuros
     */
    public function destroy(Employee $employee): void
    {
        $hasFutureAppointments = DB::table('appointments')
            ->where('employee_id', $employee->id)
            ->where('status', 'scheduled')
            ->where('starts_at', '>', now())
            ->exists();

        if ($hasFutureAppointments) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Não é possível excluir um funcionário com agendamentos futuros. Cancele-os primeiro.',
                ], 409)
            );
        }

        $employee->delete();
    }

    /**
     * Sincroniza os serviços (competências) de um funcionário.
     * Garante que todos os service_ids pertencem à mesma empresa.
     *
     * @param  array<int>  $serviceIds
     */
    public function syncServices(Employee $employee, array $serviceIds): Employee
    {
        // Filtra apenas serviços que pertencem à empresa do funcionário
        $validIds = DB::table('services')
            ->where('company_id', $employee->company_id)
            ->whereIn('id', $serviceIds)
            ->pluck('id')
            ->toArray();

        $employee->services()->sync($validIds);

        return $employee->load('services:id,name');
    }
}
