<?php

namespace App\Policies;

use App\Models\Status;
use App\Models\User;

class StatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->temPermissao('statuses.visualizar');
    }

    public function view(User $user, Status $registro): bool
    {
        return $user->temPermissao('statuses.visualizar');
    }

    public function create(User $user): bool
    {
        return $user->temPermissao('statuses.criar');
    }

    public function update(User $user, Status $registro): bool
    {
        return $user->temPermissao('statuses.editar');
    }

    public function delete(User $user, Status $registro): bool
    {
        return $user->temPermissao('statuses.excluir');
    }
}
