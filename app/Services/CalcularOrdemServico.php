<?php

namespace App\Services;

use App\Enums\TipoCobranca;
use Illuminate\Validation\ValidationException;

class CalcularOrdemServico
{
    /**
     * Recalcula cada item e devolve os valores que serão persistidos.
     *
     * @param  array<int, array<string, mixed>>  $itens
     * @return array<int, array<string, mixed>>
     */
    public function itens(array $itens): array
    {
        $calculados = [];

        foreach (array_values($itens) as $posicao => $item) {
            $tipo = TipoCobranca::tryFrom((string) ($item['tipo'] ?? TipoCobranca::Unitario->value)) ?? TipoCobranca::Unitario;
            $quantidade = (float) ($item['quantidade'] ?? 0);
            $item['tipo'] = $tipo->value;
            $item['quantidade'] = $quantidade;

            if ($tipo->isMetroQuadrado()) {
                $largura = (float) ($item['largura'] ?? 0);
                $altura = (float) ($item['altura'] ?? 0);
                $area = round($largura * $altura, 4);
                $valorM2 = (float) ($item['valor_m2'] ?? 0);
                $item['largura'] = $largura;
                $item['altura'] = $altura;
                $item['area'] = $area;
                $item['valor_m2'] = $valorM2;
                $item['valor_unitario'] = 0;
                $item['subtotal'] = round($area * $quantidade * $valorM2, 2);
            } else {
                $valorUnitario = (float) ($item['valor_unitario'] ?? 0);
                $item['largura'] = null;
                $item['altura'] = null;
                $item['area'] = null;
                $item['valor_unitario'] = $valorUnitario;
                $item['valor_m2'] = 0;
                $item['subtotal'] = round($quantidade * $valorUnitario, 2);
            }

            $item['posicao'] = $posicao;
            $calculados[] = $item;
        }

        return $calculados;
    }

    /**
     * Calcula os totais da OS a partir dos itens já calculados.
     *
     * @param  array<int, array<string, mixed>>  $itens
     * @param  array<string, mixed>  $dados
     * @return array<string, float>
     */
    public function totais(array $itens, array $dados): array
    {
        $quantidade = array_sum(array_map(fn (array $item) => (float) $item['quantidade'], $itens));
        $totalM2 = array_sum(array_map(fn (array $item) => (float) ($item['area'] ?? 0), $itens));
        $valorTotal = round(array_sum(array_map(fn (array $item) => (float) $item['subtotal'], $itens)), 2);
        $desconto = (float) ($dados['desconto'] ?? 0);
        $valorFinal = round($valorTotal - $desconto, 2);

        if ($valorFinal < 0 || $valorFinal > 9999999999999.99) {
            throw ValidationException::withMessages(['desconto' => 'O desconto não pode ser maior que o valor dos produtos.']);
        }

        return [
            'quantidade' => round($quantidade, 4),
            'total_m2' => round($totalM2, 4),
            'valor_total' => $valorTotal,
            'valor_produtos' => $valorTotal,
            'desconto' => $desconto,
            'valor_total_final' => $valorFinal,
        ];
    }
}
