<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory;

    public const DADOS_OS = ['nome', 'tipo_pessoa', 'documento', 'endereco', 'numero_endereco', 'cidade', 'bairro', 'uf', 'cep', 'telefone'];

    protected $fillable = [...self::DADOS_OS, 'email', 'observacoes'];

    public function documentoRotulo(): string
    {
        return ($this->tipo_pessoa ?? (strlen(preg_replace('/\D/', '', (string) $this->documento)) === 14 ? 'PJ' : 'PF')) === 'PJ' ? 'CNPJ' : 'CPF';
    }

    public function ordens(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }
}
