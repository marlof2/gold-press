<?php

namespace App\Policies;

use App\Models\Perfil;
use App\Models\User;

class PerfilPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPermissao('perfis.visualizar');
    }

    public function view(User $user, Perfil $registro): bool
    {
        return $user->temPermissao('perfis.visualizar');
    }

    public function update(User $user, Perfil $registro): bool
    {
        return $user->temPermissao('perfis.editar');
    }
}
