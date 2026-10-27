<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use HasFactory;

    protected $table = 'ordens_servico';

    protected $fillable = ['cliente_id', 'status_id', 'nome', 'documento', 'endereco', 'numero_endereco', 'cidade', 'bairro', 'uf', 'cep', 'telefone', 'descricao', 'responsavel', 'condicoes_pagamento', 'total_m2', 'quantidade', 'valor_total', 'valor_produtos', 'desconto', 'valor_total_final', 'observacoes', 'servico_realizado', 'situacao', 'data_aprovacao', 'data_entrega', 'arquivo_path', 'token'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class)->orderBy('posicao')->orderBy('id');
    }

    protected function casts(): array
    {
        return [
            'total_m2' => 'decimal:4',
            'quantidade' => 'decimal:4',
            'valor_total' => 'decimal:2',
            'valor_produtos' => 'decimal:2',
            'desconto' => 'decimal:2',
            'valor_total_final' => 'decimal:2',
            'data_aprovacao' => 'date',
            'data_entrega' => 'date',
        ];
    }
}
