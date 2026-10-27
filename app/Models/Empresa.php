<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['nome', 'razao_social', 'documento', 'endereco', 'telefone', 'email', 'site'];

    /**
     * Retorna o registro único de configuração da empresa, mesmo antes de existir no banco.
     */
    public static function atual(): self
    {
        return static::query()->oldest('id')->first() ?? new static(['nome' => 'Gold Press']);
    }
}
