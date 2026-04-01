<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * Registra uma nova empresa e cria o usuário admin.
     * Plano inicial: free, trial de 3 dias.
     *
     * @return array{company: Company, user: User, token: string}
     */
    public function register(array $data): array
    {
        $company = Company::create([
            'name' => $data['company_name'],
            'slug' => $this->generateUniqueSlug($data['company_name']),
            'plan' => 'free',
            'trial_ends_at' => now()->addDays(3),
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('company', 'user', 'token');
    }

    /**
     * Autentica um usuário (admin ou funcionário) e retorna token Sanctum.
     *
     * @return array{user: User, token: string}
     *
     * @throws AuthenticationException
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new AuthenticationException('Credenciais inválidas.');
        }

        // Revoga tokens anteriores para manter apenas 1 sessão ativa por usuário
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Revoga o token atual do usuário autenticado.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    private function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Company::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
