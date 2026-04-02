<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateCompanyRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Empresa cadastrada com sucesso. Seu trial de 3 dias começou!',
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'role' => $result['user']->role,
            ],
            'company' => [
                'id' => $result['company']->id,
                'name' => $result['company']->name,
                'slug' => $result['company']->slug,
                'plan' => $result['company']->plan,
                'trial_ends_at' => $result['company']->trial_ends_at,
            ],
            'token' => $result['token'],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());
        } catch (AuthenticationException) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'user' => [
                'id' => $result['user']->id,
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'role' => $result['user']->role,
                'company_id' => $result['user']->company_id,
            ],
            'token' => $result['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('company');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'company_id' => $user->company_id,
            'company' => [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'slug' => $user->company->slug,
                'plan' => $user->company->plan,
                'trial_ends_at' => $user->company->trial_ends_at,
                'subscription_ends_at' => $user->company->subscription_ends_at,
                'subscription_expiring_soon' => $user->company->subscription_expiring_soon,
            ],
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->name  = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    public function updateCompany(UpdateCompanyRequest $request): JsonResponse
    {
        $company = $request->user()->company;
        $data    = $request->validated();

        $company->name = $data['name'];
        $company->slug = $data['slug'];
        $company->save();

        return response()->json([
            'message' => 'Dados da organização atualizados com sucesso.',
            'company' => [
                'id'   => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
            ],
        ]);
    }
}
