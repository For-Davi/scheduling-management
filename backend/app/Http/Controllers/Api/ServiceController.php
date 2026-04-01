<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceService $serviceService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Service::class);

        $services = $this->serviceService->list($request->user()->company);

        return response()->json($services);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = $this->serviceService->store(
            $request->user()->company,
            $request->validated()
        );

        return response()->json($service, 201);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $updated = $this->serviceService->update($service, $request->validated());

        return response()->json($updated);
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorize('delete', $service);

        $this->serviceService->destroy($service);

        return response()->json(['message' => 'Serviço excluído com sucesso.']);
    }
}
