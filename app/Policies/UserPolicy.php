<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPermissao('usuarios.visualizar');
    }

    public function view(User $user, User $registro): bool
    {
        return $user->temPermissao('usuarios.visualizar');
    }

    public function create(User $user): bool
    {
        return $user->temPermissao('usuarios.criar');
    }

    public function update(User $user, User $registro): bool
    {
        return $user->temPermissao('usuarios.editar');
    }

    public function delete(User $user, User $registro): bool
    {
        return $user->temPermissao('usuarios.excluir');
    }
}
