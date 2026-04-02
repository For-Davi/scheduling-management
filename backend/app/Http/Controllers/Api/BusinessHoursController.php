<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockedDay\StoreBlockedDayRequest;
use App\Http\Requests\BusinessHour\UpdateBusinessHoursRequest;
use App\Models\BlockedDay;
use App\Services\BusinessHoursService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessHoursController extends Controller
{
    public function __construct(private BusinessHoursService $service) {}

    // -------------------------------------------------------------------------
    // Business Hours
    // -------------------------------------------------------------------------

    /**
     * GET /api/business-hours
     * Retorna os 7 dias configurados + buffer da empresa.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getBusinessHours($request->user()->company);

        return response()->json($data);
    }

    /**
     * PUT /api/business-hours
     * Atualiza os 7 dias e o buffer. Somente admin.
     */
    public function update(UpdateBusinessHoursRequest $request): JsonResponse
    {
        $data = $this->service->updateBusinessHours(
            $request->user()->company,
            $request->validated()
        );

        return response()->json($data);
    }

    // -------------------------------------------------------------------------
    // Blocked Days
    // -------------------------------------------------------------------------

    /**
     * GET /api/blocked-days
     * Admin: todos os bloqueios da empresa.
     * Funcionário: apenas os seus.
     */
    public function indexBlockedDays(Request $request): JsonResponse
    {
        $blockedDays = $this->service->getBlockedDays(
            $request->user()->company,
            $request->user()
        );

        return response()->json($blockedDays);
    }

    /**
     * POST /api/blocked-days
     * Admin: bloqueia dia da empresa ou de um funcionário específico.
     * Funcionário: bloqueia apenas seu próprio dia.
     */
    public function storeBlockedDay(StoreBlockedDayRequest $request): JsonResponse
    {
        $blockedDay = $this->service->storeBlockedDay(
            $request->user()->company,
            $request->user(),
            $request->validated()
        );

        return response()->json($blockedDay, 201);
    }

    /**
     * DELETE /api/blocked-days/{blockedDay}
     * Admin: qualquer bloqueio da empresa. Funcionário: apenas os seus.
     */
    public function destroyBlockedDay(Request $request, BlockedDay $blockedDay): JsonResponse
    {
        $this->service->destroyBlockedDay(
            $request->user()->company,
            $request->user(),
            $blockedDay
        );

        return response()->json(['message' => 'Bloqueio removido com sucesso.']);
    }
}
