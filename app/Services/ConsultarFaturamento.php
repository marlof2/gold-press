<?php

namespace App\Services;

use App\Models\OrdemServico;
use App\Models\Pagamento;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class ConsultarFaturamento
{
    /** @return array<string, mixed> */
    public function consultar(CarbonImmutable $inicio, CarbonImmutable $fim): array
    {
        $ordens = OrdemServico::query()
            ->where('created_at', '>=', $inicio->startOfDay())
            ->where('created_at', '<', $fim->addDay()->startOfDay())
            ->where(function (Builder $query): void {
                $query->whereHas('status', fn (Builder $status) => $status->where('slug', '!=', 'cancelada'))
                    ->orWhere(function (Builder $legadas): void {
                        $legadas->whereDoesntHave('status')->where(function (Builder $situacao): void {
                            $situacao->whereNull('situacao')->orWhereNotIn('situacao', ['cancelada', 'Cancelada']);
                        });
                    });
            })
            ->selectRaw('substr(created_at, 1, 7) as mes, COUNT(*) as quantidade, SUM(valor_total_final) as faturado, SUM(desconto) as descontos')
            ->groupByRaw('substr(created_at, 1, 7)')->get()->keyBy('mes');
        $pagamentos = Pagamento::query()->whereNull('estornado_em')
            ->whereBetween('data_pagamento', [$inicio->toDateString(), $fim->toDateString()])
            ->selectRaw('substr(data_pagamento, 1, 7) as mes, SUM(valor) as recebido')
            ->groupByRaw('substr(data_pagamento, 1, 7)')->get()->keyBy('mes');

        $meses = [];
        for ($mes = $inicio->startOfMonth(); $mes <= $fim->startOfMonth(); $mes = $mes->addMonth()) {
            $chave = $mes->format('Y-m');
            $ordem = $ordens->get($chave);
            $recebimento = $pagamentos->get($chave);
            $meses[] = [
                'mes' => $chave,
                'rotulo' => $mes->locale('pt_BR')->translatedFormat('M/Y'),
                'quantidade' => (int) ($ordem?->quantidade ?? 0),
                'faturado_centavos' => (int) round((float) ($ordem?->faturado ?? 0) * 100),
                'recebido_centavos' => (int) round((float) ($recebimento?->recebido ?? 0) * 100),
                'descontos_centavos' => (int) round((float) ($ordem?->descontos ?? 0) * 100),
            ];
        }
        $faturado = array_sum(array_column($meses, 'faturado_centavos'));
        $quantidade = array_sum(array_column($meses, 'quantidade'));

        return [
            'meses' => $meses,
            'totais' => [
                'faturado_centavos' => $faturado,
                'recebido_centavos' => array_sum(array_column($meses, 'recebido_centavos')),
                'descontos_centavos' => array_sum(array_column($meses, 'descontos_centavos')),
                'quantidade' => $quantidade,
                'ticket_centavos' => $quantidade ? (int) round($faturado / $quantidade) : 0,
            ],
        ];
    }
}
