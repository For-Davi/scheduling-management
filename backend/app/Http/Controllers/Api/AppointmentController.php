<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $query = Appointment::with(['service', 'employee'])
            ->where('company_id', $company->id)
            ->orderBy('starts_at');

        if ($request->filled('date')) {
            $query->whereDate('starts_at', $request->date);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function myAppointments(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Appointment::with(['service', 'employee'])
            ->where('company_id', $user->company->id)
            ->where('employee_id', $user->employee?->id)
            ->orderBy('starts_at');

        if ($request->filled('date')) {
            $query->whereDate('starts_at', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }

    public function updateStatus(Request $request, Appointment $appointment): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::in(['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])],
        ]);

        $oldStatus = $appointment->status;
        $appointment->update(['status' => $request->status]);

        AppointmentLog::create([
            'appointment_id' => $appointment->id,
            'user_id'        => $request->user()->id,
            'old_status'     => $oldStatus,
            'new_status'     => $request->status,
            'changed_at'     => now(),
        ]);

        return response()->json($appointment->load(['service', 'employee']));
    }
}
