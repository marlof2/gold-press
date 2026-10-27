<?php

namespace App\Models;

use App\Enums\TipoCobranca;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'tipo', 'unidade', 'valor_unitario', 'valor_m2', 'ativo'];

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class);
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoCobranca::class,
            'valor_unitario' => 'decimal:2',
            'valor_m2' => 'decimal:2',
            'ativo' => 'boolean',
        ];
    }
}
