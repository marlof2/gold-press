<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\ModeloWhatsApp;
use App\Models\OrdemServico;

class MensagensWhatsApp
{
    public const VARIAVEIS = [
        'cliente' => 'Nome do cliente',
        'empresa' => 'Nome da empresa',
        'numero' => 'Número da OS',
        'itens' => 'Lista de itens (até 8; demais no PDF)',
        'total' => 'Valor total',
        'pago' => 'Valor já pago',
        'saldo' => 'Saldo a pagar',
        'entrega' => 'Data de entrega ou “A combinar”',
        'documento' => 'Link do PDF atualizado',
        'endereco_empresa' => 'Endereço da empresa',
        'abertura' => 'Saudação com cliente e empresa',
        'financeiro' => 'Resumo do pagamento',
        'prazo' => 'Frase com a previsão de entrega',
        'retirada' => 'Frase com o local de retirada',
        'fecho' => 'Encerramento com link do PDF',
    ];

    /** @return array<string, string> */
    public function exemplo(): array
    {
        $documento = 'https://exemplo.com/documentos/pedido.pdf';

        return [
            'cliente' => 'Maria Silva',
            'empresa' => 'Gold Press',
            'numero' => '000123',
            'itens' => '• Cartões de visita — 500 un',
            'total' => 'R$ 150,00',
            'pago' => 'R$ 50,00',
            'saldo' => 'R$ 100,00',
            'entrega' => '15/11/2026',
            'documento' => $documento,
            'endereco_empresa' => 'Rua das Flores, 123 — Centro',
            'abertura' => 'Olá, Maria Silva! Aqui é da Gold Press.',
            'financeiro' => "*Total do pedido: R$ 150,00*\nJá pago: R$ 50,00\n*Valor a pagar: R$ 100,00*",
            'prazo' => "\nPrevisão de entrega: 15/11/2026",
            'retirada' => "\nLocal de retirada: Rua das Flores, 123 — Centro",
            'fecho' => "\n\nDetalhes e PDF atualizado:\n".$documento."\n\nSe precisar de ajuda, fale com a gente por aqui.",
        ];
    }

    /** @return array<string, string> */
    public function nomes(): array
    {
        return ModeloWhatsApp::orderBy('posicao')->orderBy('id')->pluck('nome', 'chave')->all();
    }

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

        $variaveis = [
            '{cliente}' => $ordem->nome,
            '{empresa}' => $empresa->nome,
            '{numero}' => $numero,
            '{itens}' => $itens,
            '{total}' => $moeda((float) $ordem->valor_total_final),
            '{pago}' => $moeda($pago),
            '{saldo}' => $moeda($saldo),
            '{entrega}' => $ordem->data_entrega?->format('d/m/Y') ?? 'A combinar',
            '{documento}' => $documento,
            '{endereco_empresa}' => $empresa->endereco ?? '',
            '{abertura}' => $abertura,
            '{financeiro}' => $financeiro,
            '{prazo}' => $prazo,
            '{retirada}' => $retirada,
            '{fecho}' => $fecho,
        ];

        return ModeloWhatsApp::orderBy('posicao')->orderBy('id')->get()
            ->mapWithKeys(fn (ModeloWhatsApp $modelo): array => [$modelo->chave => strtr($modelo->conteudo, $variaveis)])->all();
    }
}
