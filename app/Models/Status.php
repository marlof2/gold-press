<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'slug', 'cor', 'posicao', 'finalizado'];

    protected function casts(): array
    {
        return ['finalizado' => 'boolean', 'posicao' => 'integer'];
    }

    public function ordens(): HasMany
    {
        return $this->hasMany(OrdemServico::class);
    }
}
