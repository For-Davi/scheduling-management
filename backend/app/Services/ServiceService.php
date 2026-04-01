<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class ServiceService
{
    /**
     * Lista todos os serviços da empresa (ativos e inativos).
     *
     * @return Collection<int, Service>
     */
    public function list(Company $company): Collection
    {
        return $company->services()->orderBy('name')->get();
    }

    /**
     * Cria um serviço, aplicando a trava de limite do plano (regra de negócio #3).
     *
     * @throws HttpResponseException HTTP 403 se o limite foi atingido
     */
    public function store(Company $company, array $data): Service
    {
        $activeCount = $company->services()->where('is_active', true)->count();
        $limit = $company->planLimit('services');

        if ($activeCount >= $limit) {
            throw new HttpResponseException(
                response()->json([
                    'message' => "Limite de serviços do plano {$company->plan} atingido ({$limit}). Faça upgrade para adicionar mais.",
                ], 403)
            );
        }

        return $company->services()->create($data);
    }

    /**
     * Atualiza os dados de um serviço.
     */
    public function update(Service $service, array $data): Service
    {
        $service->update($data);

        return $service->fresh();
    }

    /**
     * Remove um serviço.
     *
     * @throws HttpResponseException HTTP 409 se houver agendamentos futuros vinculados
     */
    public function destroy(Service $service): void
    {
        $hasFutureAppointments = DB::table('appointments')
            ->where('service_id', $service->id)
            ->where('status', 'scheduled')
            ->where('starts_at', '>', now())
            ->exists();

        if ($hasFutureAppointments) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Não é possível excluir um serviço com agendamentos futuros. Cancele-os primeiro.',
                ], 409)
            );
        }

        $service->delete();
    }
}
