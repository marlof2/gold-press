<?php

namespace App\Models;

use App\Enums\TipoCobranca;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdemServicoItem extends Model
{
    use HasFactory;

    protected $table = 'ordem_servico_itens';

    protected $fillable = ['ordem_servico_id', 'produto_id', 'tipo', 'descricao', 'unidade', 'codigo', 'referencia', 'formato', 'vias', 'cores', 'quantidade', 'largura', 'altura', 'area', 'valor_unitario', 'valor_m2', 'subtotal', 'posicao'];

    public function ordem(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function medidas(): string
    {
        if ($this->tipo !== TipoCobranca::MetroQuadrado) {
            return '';
        }

        return number_format((float) $this->largura, 2, ',', '.').' x '.number_format((float) $this->altura, 2, ',', '.').' = '.number_format((float) $this->area, 4, ',', '.').' m²';
    }

    protected function casts(): array
    {
        return [
            'tipo' => TipoCobranca::class,
            'quantidade' => 'decimal:4',
            'largura' => 'decimal:4',
            'altura' => 'decimal:4',
            'area' => 'decimal:4',
            'valor_unitario' => 'decimal:2',
            'valor_m2' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'posicao' => 'integer',
        ];
    }
}
