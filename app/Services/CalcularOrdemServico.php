<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class CalcularOrdemServico
{
    public function calcular(array $dados): array
    {
        foreach (['altura', 'largura', 'valor_produto_m2', 'quantidade', 'arredondamento', 'acerto_valor', 'valor_servicos', 'deslocamento', 'desconto'] as $campo) {
            $dados[$campo] = (float) ($dados[$campo] ?? 0);
        }
        // Medidas em metros; cada etapa monetária é arredondada para centavos.
        $dados['total_m2'] = round($dados['altura'] * $dados['largura'], 4);
        $dados['valor_total_m2'] = round($dados['total_m2'] * $dados['valor_produto_m2'], 2);
        $dados['valor_total'] = round($dados['valor_total_m2'] * $dados['quantidade'], 2);
        $dados['valor_produtos'] = round($dados['valor_total'] + $dados['arredondamento'] + $dados['acerto_valor'], 2);
        $dados['valor_total_final'] = round($dados['valor_produtos'] + $dados['valor_servicos'] + $dados['deslocamento'] - $dados['desconto'], 2);
        if ($dados['valor_produtos'] < 0 || $dados['valor_total_final'] < 0 || $dados['valor_total_final'] > 9999999999999.99) {
            throw ValidationException::withMessages(['desconto' => 'Os ajustes e descontos devem resultar em totais válidos, maiores ou iguais a zero.']);
        }

        return $dados;
    }
}
