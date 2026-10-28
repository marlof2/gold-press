<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use HasFactory;

    protected $table = 'ordens_servico';

    protected $fillable = ['cliente_id', 'status_id', 'nome', 'documento', 'endereco', 'numero_endereco', 'cidade', 'bairro', 'uf', 'cep', 'telefone', 'descricao', 'responsavel_id', 'responsavel', 'condicoes_pagamento', 'total_m2', 'quantidade', 'valor_total', 'valor_produtos', 'desconto', 'valor_total_final', 'observacoes', 'servico_realizado', 'situacao', 'data_aprovacao', 'data_entrega', 'token'];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function usuarioResponsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(Status::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrdemServicoItem::class)->orderBy('posicao')->orderBy('id');
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class)->orderByDesc('data_pagamento')->orderByDesc('id');
    }

    public function valorPago(): float
    {
        return round((float) ($this->relationLoaded('pagamentos') ? $this->pagamentos->whereNull('estornado_em')->sum('valor') : $this->pagamentos()->whereNull('estornado_em')->sum('valor')), 2);
    }

    public function saldoRestante(): float
    {
        return max(0, round((float) $this->valor_total_final - $this->valorPago(), 2));
    }

    public function urlMaps(): ?string
    {
        if (blank($this->endereco) && blank($this->cep)) {
            return null;
        }

        $endereco = collect([$this->endereco, $this->numero_endereco, $this->bairro, $this->cidade, $this->uf, $this->cep, 'Brasil'])
            ->filter(fn ($parte) => filled($parte))
            ->map(fn ($parte) => trim((string) $parte))
            ->join(', ');

        return 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($endereco);
    }

    public function scopeAbertas(Builder $query): Builder
    {
        return $query->whereHas('status', fn (Builder $status) => $status->where('finalizado', false));
    }

    public function scopePrazo(Builder $query, ?string $prazo): Builder
    {
        return match ($prazo) {
            'abertas' => $query->abertas(),
            'hoje' => $query->abertas()->whereDate('data_entrega', today()),
            'proximas' => $query->abertas()->whereBetween('data_entrega', [today()->addDay()->toDateString(), today()->addDays(7)->toDateString()]),
            'atrasadas' => $query->abertas()->whereDate('data_entrega', '<', today()),
            default => $query,
        };
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
