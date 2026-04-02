<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    /**
     * GET /api/dashboard/metrics
     * Retorna todas as métricas do painel para a empresa do admin autenticado.
     */
    public function metrics(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        return response()->json($this->service->getMetrics($company));
    }
}
