<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\BusinessHoursController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Public\AvailabilityController;
use App\Http\Controllers\Public\BookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Todas as rotas aqui recebem automaticamente o prefixo /api
| e o middleware 'api' (rate limiting, etc).
|
| Autenticação via Laravel Sanctum: usar middleware('auth:sanctum')
| Controle de role: middleware('role:admin') ou middleware('role:employee')
|--------------------------------------------------------------------------
*/

// Autenticação (pública)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Rotas protegidas — exigem autenticação
Route::middleware(['auth:sanctum'])->group(function () {
    // Serviços (somente admin pode criar/editar/deletar)
    Route::get('services', [ServiceController::class, 'index']);
    Route::post('services', [ServiceController::class, 'store'])->middleware('role:admin');
    Route::put('services/{service}', [ServiceController::class, 'update'])->middleware('role:admin');
    Route::delete('services/{service}', [ServiceController::class, 'destroy'])->middleware('role:admin');

    // Funcionários (somente admin pode criar/editar/deletar/sincronizar)
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::middleware('role:admin')->group(function () {
        Route::post('employees', [EmployeeController::class, 'store']);
        Route::put('employees/{employee}', [EmployeeController::class, 'update']);
        Route::delete('employees/{employee}', [EmployeeController::class, 'destroy']);
        Route::post('employees/{employee}/services', [EmployeeController::class, 'syncServices']);
    });

    // Horários de funcionamento (GET: todos; PUT: somente admin)
    Route::get('business-hours', [BusinessHoursController::class, 'index']);
    Route::put('business-hours', [BusinessHoursController::class, 'update'])->middleware('role:admin');

    // Dias bloqueados (GET/POST/DELETE: admin e funcionário com escopos diferentes)
    Route::get('blocked-days', [BusinessHoursController::class, 'indexBlockedDays']);
    Route::post('blocked-days', [BusinessHoursController::class, 'storeBlockedDay']);
    Route::delete('blocked-days/{blockedDay}', [BusinessHoursController::class, 'destroyBlockedDay']);

    // Agendamentos
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::get('appointments/my', [AppointmentController::class, 'myAppointments']);
    Route::patch('appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);

    // Dashboard (somente admin)
    Route::get('dashboard/metrics', [DashboardController::class, 'metrics'])->middleware('role:admin');

    // Billing / Assinatura (somente admin)
    Route::middleware('role:admin')->group(function () {
        Route::get('billing', [BillingController::class, 'info']);
        Route::post('billing/plan', [BillingController::class, 'changePlan']);
        Route::post('billing/checkout', [BillingController::class, 'checkout']);
        Route::get('billing/portal', [BillingController::class, 'portal']);
    });
});

// Webhook Stripe — sem autenticação, validar assinatura internamente
Route::post('webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->middleware('throttle:60,1');

// Rotas públicas — sem autenticação, com rate limiting
Route::prefix('public')->middleware('throttle:10,1')->group(function () {
    Route::get('{slug}', [BookingController::class, 'company']);
    Route::get('{slug}/availability', AvailabilityController::class);
    Route::post('{slug}/appointments', [BookingController::class, 'store']);
});
