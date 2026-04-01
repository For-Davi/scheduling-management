<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    // Qualquer usuário autenticado pode listar serviços da sua empresa
    public function viewAny(User $user): bool
    {
        return true;
    }

    // Somente admin pode criar
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    // Admin da mesma empresa pode atualizar
    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin() && $user->company_id === $service->company_id;
    }

    // Admin da mesma empresa pode deletar
    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin() && $user->company_id === $service->company_id;
    }
}
