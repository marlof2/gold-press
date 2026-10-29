<?php

namespace App\Policies;

use App\Models\OrdemServico;
use App\Models\User;

class OrdemServicoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPermissao('ordens.visualizar');
    }

    public function view(User $user, OrdemServico $registro): bool
    {
        return $user->temPermissao('ordens.visualizar');
    }

    public function create(User $user): bool
    {
        return $user->temPermissao('ordens.criar');
    }

    public function update(User $user, OrdemServico $registro): bool
    {
        return $user->temPermissao('ordens.editar');
    }

    public function delete(User $user, OrdemServico $registro): bool
    {
        return $user->temPermissao('ordens.excluir');
    }
}
