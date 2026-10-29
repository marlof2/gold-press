<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\OrdemServico;

class MensagensWhatsApp
{
    public function telefone(string $telefone): string
    {
        $digitos = preg_replace('/\D/', '', $telefone);

        return in_array(strlen($digitos), [10, 11]) ? '55'.$digitos : $digitos;
    }

    public function url(string $telefone, string $mensagem): string
    {
        return 'https://wa.me/'.$this->telefone($telefone).'?text='.rawurlencode($mensagem);
    }

    /** @return array<string, string> */
    public function modelos(OrdemServico $ordem): array
    {
        $empresa = Empresa::atual();
        $numero = str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT);
        $moeda = fn (float $valor): string => 'R$ '.number_format($valor, 2, ',', '.');
        $abertura = 'Olá, '.$ordem->nome.'! Aqui é da '.$empresa->nome.'.';
        $documento = route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']);
        $quantidade = fn ($valor): string => rtrim(rtrim(number_format((float) $valor, 4, ',', '.'), '0'), ',');
        $itens = $ordem->itens->take(8)->map(function ($item) use ($quantidade): string {
            $medida = $item->tipo->isMetroQuadrado()
                ? $quantidade($item->quantidade).' × ('.$quantidade($item->largura).' m × '.$quantidade($item->altura).' m)'
                : $quantidade($item->quantidade).' un';

            return '• '.$item->descricao.' — '.$medida;
        })->join("\n");
        if ($ordem->itens->count() > 8) {
            $itens .= "\n".'Confira os demais '.($ordem->itens->count() - 8).' itens no PDF.';
        }
        $pago = $ordem->valorPago();
        $saldo = $ordem->saldoRestante();
        $financeiro = '*Total do pedido: '.$moeda((float) $ordem->valor_total_final).'*';
        if ($saldo <= 0) {
            $financeiro .= "\nPagamento quitado. Obrigado!";
        } elseif ($pago > 0) {
            $financeiro .= "\nJá pago: ".$moeda($pago)."\n*Valor a pagar: ".$moeda($saldo).'*';
        } else {
            $financeiro .= "\nPagamento pendente.";
        }
        $fecho = "\n\nDetalhes e PDF atualizado:\n".$documento."\n\nSe precisar de ajuda, fale com a gente por aqui.";
        $retirada = $empresa->endereco ? "\nLocal de retirada: ".$empresa->endereco : '';
        $prazo = $ordem->data_entrega ? "\nPrevisão de entrega: ".$ordem->data_entrega->format('d/m/Y') : "\nEntrega a combinar.";

        return [
            'pedido' => $abertura."\n\n*Ordem de serviço #".$numero."*\n".$itens."\n\n".$financeiro.$prazo."\nConfira os itens e nos avise se precisar de algum ajuste.".$fecho,
            'producao' => $abertura."\n\nSeu pedido #".$numero.' está em produção. Avisaremos assim que estiver pronto!'.$prazo.$fecho,
            'retirada' => $abertura."\n\nSeu pedido #".$numero.' está pronto para retirada!'.$retirada."\n\n".$financeiro."\nPodemos combinar a retirada por aqui?".$fecho,
            'envio' => $abertura."\n\nSeu pedido #".$numero." está pronto para envio!\nPodemos confirmar o endereço e combinar a entrega por aqui?\n\n".$financeiro.$fecho,
        ];
    }
}
