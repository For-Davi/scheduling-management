<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\GetAvailabilityRequest;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(private AvailabilityService $service) {}

    /**
     * GET /api/public/{slug}/availability?employee_id=&service_id=&date=
     *
     * Retorna os slots disponíveis para agendamento.
     * Rota pública — sem autenticação, com rate limiting.
     */
    public function __invoke(GetAvailabilityRequest $request, string $slug): JsonResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        // Garante que o funcionário pertence a esta empresa e está ativo
        $employee = Employee::where('id', $request->integer('employee_id'))
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Garante que o serviço pertence a esta empresa e está ativo
        $service = Service::where('id', $request->integer('service_id'))
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->firstOrFail();

        // Garante que o funcionário tem aptidão para este serviço (Regra 2)
        abort_unless(
            $employee->services()->where('service_id', $service->id)->exists(),
            422,
            'Este funcionário não realiza o serviço selecionado.'
        );

        $slots = $this->service->getAvailableSlots($company, $employee, $service, $request->input('date'));

        return response()->json($slots);
    }
}
