<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    protected $fillable = ['cliente_id', 'status_id', 'nome', 'documento', 'endereco', 'cidade', 'bairro', 'uf', 'cep', 'telefone', 'descricao', 'codigo', 'referencia', 'unidade', 'formato', 'vias', 'cores', 'complemento', 'responsavel', 'condicoes_pagamento', 'altura', 'largura', 'total_m2', 'valor_produto_m2', 'valor_total_m2', 'quantidade', 'valor_total', 'arredondamento', 'acerto_valor', 'valor_produtos', 'valor_servicos', 'deslocamento', 'desconto', 'valor_total_final', 'observacoes', 'servico_realizado', 'situacao', 'data_aprovacao', 'data_entrega', 'arquivo_path', 'token'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    protected function casts(): array
    {
        return [
            'altura' => 'decimal:4',
            'largura' => 'decimal:4',
            'total_m2' => 'decimal:4',
            'valor_produto_m2' => 'decimal:2',
            'valor_total_m2' => 'decimal:2',
            'quantidade' => 'decimal:4',
            'valor_total' => 'decimal:2',
            'arredondamento' => 'decimal:2',
            'acerto_valor' => 'decimal:2',
            'valor_produtos' => 'decimal:2',
            'valor_servicos' => 'decimal:2',
            'deslocamento' => 'decimal:2',
            'desconto' => 'decimal:2',
            'valor_total_final' => 'decimal:2',
            'data_aprovacao' => 'date',
            'data_entrega' => 'date',
        ];
    }
}
