<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    protected $fillable = ['chave', 'valor', 'data_pagamento', 'forma', 'observacoes', 'user_id'];

    protected function casts(): array
    {
        return ['valor' => 'decimal:2', 'data_pagamento' => 'date', 'estornado_em' => 'datetime'];
    }

    public function ordem(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
