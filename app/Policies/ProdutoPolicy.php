<?php

namespace App\Policies;

use App\Models\Produto;
use App\Models\User;

class ProdutoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPermissao('produtos.visualizar');
    }

    public function view(User $user, Produto $registro): bool
    {
        return $user->temPermissao('produtos.visualizar');
    }

    public function create(User $user): bool
    {
        return $user->temPermissao('produtos.criar');
    }

    public function update(User $user, Produto $registro): bool
    {
        return $user->temPermissao('produtos.editar');
    }

    public function delete(User $user, Produto $registro): bool
    {
        return $user->temPermissao('produtos.excluir');
    }
}
