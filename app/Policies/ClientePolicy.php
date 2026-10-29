<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPermissao('clientes.visualizar');
    }

    public function view(User $user, Cliente $registro): bool
    {
        return $user->temPermissao('clientes.visualizar');
    }

    public function create(User $user): bool
    {
        return $user->temPermissao('clientes.criar');
    }

    public function update(User $user, Cliente $registro): bool
    {
        return $user->temPermissao('clientes.editar');
    }

    public function delete(User $user, Cliente $registro): bool
    {
        return $user->temPermissao('clientes.excluir');
    }
}
