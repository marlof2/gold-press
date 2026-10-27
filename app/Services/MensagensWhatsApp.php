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
        $abertura = 'Olá, '.$ordem->nome.'! Tudo bem? Aqui é da '.$empresa->nome.'.';
        $documento = route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']);
        $itens = $ordem->itens->take(8)->map(fn ($item) => '• '.$item->descricao.' — '.rtrim(rtrim(number_format((float) $item->quantidade, 4, ',', '.'), '0'), ',').' '.$item->unidade)->join("\n");
        if ($ordem->itens->count() > 8) {
            $itens .= "\n".'E mais '.($ordem->itens->count() - 8).' itens no documento.';
        }
        $financeiro = 'Valor total: '.$moeda((float) $ordem->valor_total_final)."\n".'Já recebido: '.$moeda($ordem->valorPago())."\n".'Saldo restante: '.$moeda($ordem->saldoRestante());
        $fecho = "\n\nConfira os detalhes da OS #".$numero.":\n".$documento."\n\nQualquer dúvida, estamos à disposição!";
        $retirada = $empresa->endereco ? "\nLocal de retirada: ".$empresa->endereco : '';

        return [
            'pedido' => $abertura."\n\nSegue a OS #".$numero." para sua conferência:\n".$itens."\n\n".$financeiro.($ordem->data_entrega ? "\nPrevisão de entrega: ".$ordem->data_entrega->format('d/m/Y') : '').$fecho,
            'producao' => $abertura."\n\nSeu pedido da OS #".$numero.' está em produção. Avisaremos assim que estiver pronto!'.$fecho,
            'retirada' => $abertura."\n\nSeu pedido da OS #".$numero.' está pronto para retirada!'.$retirada."\n\n".$financeiro."\nPodemos combinar a retirada por aqui?".$fecho,
            'envio' => $abertura."\n\nSeu pedido da OS #".$numero." está pronto para envio!\nPodemos confirmar o endereço e combinar a entrega por aqui?\n\n".$financeiro.$fecho,
        ];
    }
}
