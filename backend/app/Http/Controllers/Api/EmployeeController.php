<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\SyncServicesRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employeeService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $employees = $this->employeeService->list($request->user()->company);

        return response()->json($employees);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->store(
            $request->user()->company,
            $request->validated()
        );

        return response()->json($employee, 201);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $updated = $this->employeeService->update($employee, $request->validated());

        return response()->json($updated);
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $this->employeeService->destroy($employee);

        return response()->json(['message' => 'Funcionário excluído com sucesso.']);
    }

    public function syncServices(SyncServicesRequest $request, Employee $employee): JsonResponse
    {
        $updated = $this->employeeService->syncServices(
            $employee,
            $request->validated('service_ids')
        );

        return response()->json($updated);
    }
}
