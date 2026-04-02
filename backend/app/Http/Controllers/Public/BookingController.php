<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicAppointmentRequest;
use App\Models\Company;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(private BookingService $service) {}

    /**
     * GET /api/public/{slug}
     * Retorna dados públicos da empresa: nome, serviços ativos e funcionários aptos.
     */
    public function company(string $slug): JsonResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $services = $company->services()
            ->where('is_active', true)
            ->select(['id', 'company_id', 'name', 'price', 'duration_minutes', 'description'])
            ->orderBy('name')
            ->get();

        // Funcionários ativos com a lista de IDs de serviços que realizam
        $employees = $company->employees()
            ->where('is_active', true)
            ->select(['id', 'company_id', 'name'])
            ->orderBy('name')
            ->get()
            ->each(fn($e) => $e->load(['services' => fn($q) => $q->select('services.id')]))
            ->map(fn($e) => [
                'id'       => $e->id,
                'name'     => $e->name,
                'services' => $e->services->map(fn($s) => ['id' => $s->id])->values(),
            ]);

        return response()->json([
            'id'        => $company->id,
            'name'      => $company->name,
            'slug'      => $company->slug,
            'services'  => $services,
            'employees' => $employees,
        ]);
    }

    /**
     * POST /api/public/{slug}/appointments
     * Cria um agendamento público. Aplica regras 1, 2, 4 e 8.
     */
    public function store(StorePublicAppointmentRequest $request, string $slug): JsonResponse
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $appointment = $this->service->storeAppointment($company, $request->validated());

        return response()->json(
            $appointment->load(['employee:id,name', 'service:id,name,duration_minutes']),
            201
        );
    }
}
