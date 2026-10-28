<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;

class DadosDocumentoOrdemServico
{
    /** @return array<string, mixed> */
    public function para(OrdemServico $ordem, ?Empresa $empresa = null): array
    {
        $ordem->loadMissing('itens', 'status', 'pagamentos');
        $empresa ??= Empresa::atual();
        $logo = public_path('img/logo-gold-press.png');
        $prazos = [
            'Emissão' => $ordem->created_at->format('d/m/Y'),
            'Entrega prevista' => $ordem->data_entrega?->format('d/m/Y') ?? 'A combinar',
        ];
        if ($ordem->data_aprovacao) {
            $prazos['Aprovação'] = $ordem->data_aprovacao->format('d/m/Y');
        }

        return [
            'numero' => str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT),
            'logo_path' => is_file($logo) ? $logo : null,
            'logo' => is_file($logo) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logo)) : null,
            'empresa' => $empresa->nome,
            'empresa_linhas' => array_values(array_filter([
                $empresa->documento ? 'CNPJ '.$empresa->documento : null,
                $empresa->endereco, $empresa->telefone, $empresa->email, $empresa->site,
            ], fn ($valor) => filled($valor))),
            'status' => $ordem->status?->nome ?: $ordem->situacao,
            'cliente' => $ordem->nome,
            'documento' => $ordem->documento ?: '—',
            'telefone' => $ordem->telefone ?: '—',
            'endereco' => collect([$ordem->endereco, $ordem->numero_endereco, $ordem->bairro, $ordem->cidade, $ordem->uf, $ordem->cep])->filter()->join(' · ') ?: 'Não informado',
            'prazos' => $prazos,
            'responsavel' => $ordem->responsavel ?: 'Não definido',
            'itens' => $ordem->itens->map(fn (OrdemServicoItem $item): array => [
                'descricao' => $item->descricao,
                'detalhes' => array_values(array_filter([
                    collect([$item->codigo ? 'Cód.: '.$item->codigo : null, $item->referencia ? 'Ref.: '.$item->referencia : null])->filter()->join(' · '),
                    collect([$item->formato, $item->cores, $item->vias ? 'Vias: '.$item->vias : null])->filter()->join(' · '),
                ])),
                'quantidade' => rtrim(rtrim(number_format((float) $item->quantidade, 4, ',', '.'), '0'), ',').' '.$item->tipo->unidade(),
                'medidas' => $item->medidas() ?: '—',
                'preco' => $this->moeda($item->tipo->isMetroQuadrado() ? $item->valor_m2 : $item->valor_unitario),
                'preco_unidade' => $item->tipo->isMetroQuadrado() ? 'por m²' : 'por unidade',
                'subtotal' => $this->moeda($item->subtotal),
            ])->all(),
            'totais' => [
                'Subtotal' => $this->moeda($ordem->valor_total),
                'Desconto' => $this->moeda($ordem->desconto),
                'Total da OS' => $this->moeda($ordem->valor_total_final),
                'Valor pago' => $this->moeda($ordem->valorPago()),
                'Saldo restante' => $this->moeda($ordem->saldoRestante()),
            ],
            'notas' => array_filter([
                'Condições de pagamento' => $ordem->condicoes_pagamento,
                'Observações' => $ordem->observacoes,
                'Serviço realizado' => $ordem->servico_realizado,
            ], fn ($valor) => filled($valor)),
        ];
    }

    private function moeda(mixed $valor): string
    {
        return 'R$ '.number_format((float) $valor, 2, ',', '.');
    }
}
