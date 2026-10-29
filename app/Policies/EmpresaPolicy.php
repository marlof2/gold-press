<?php

namespace App\Policies;

use App\Models\Empresa;
use App\Models\User;

class EmpresaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPermissao('empresa.visualizar');
    }

    public function view(User $user, Empresa $registro): bool
    {
        return $user->temPermissao('empresa.visualizar');
    }

    public function update(User $user, Empresa $registro): bool
    {
        return $user->temPermissao('empresa.editar');
    }
}
