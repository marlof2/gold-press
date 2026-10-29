<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perfil extends Model
{
    public const ADMIN = 'admin';

    public const OPERADOR = 'operador';

    protected $table = 'perfis';

    protected $fillable = ['nome', 'slug', 'descricao'];

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissao', 'perfil_id', 'permissao_id')->withTimestamps();
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->slug === self::ADMIN;
    }
}
