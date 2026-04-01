<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\ServiceController;
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
});
