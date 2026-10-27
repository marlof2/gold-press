<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\OrdemServico;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;

class GerarDocumentoOrdemServico
{
    private const LARGURA = 11300;

    private const MARGEM_CELULA = 20;

    public function gerar(OrdemServico $ordem): string
    {
        Settings::setOutputEscapingEnabled(true);

        $doc = new PhpWord;
        $doc->setDefaultFontName('Courier New');
        $doc->setDefaultFontSize(8);
        $section = $doc->addSection([
            'orientation' => 'portrait',
            'marginTop' => 250,
            'marginBottom' => 250,
            'marginLeft' => 300,
            'marginRight' => 300,
        ]);

        $empresa = Empresa::atual();
        $this->cabecalho($section, $empresa);
        $this->titulo($section, $ordem);
        $this->cliente($section, $ordem);
        $this->itens($section, $ordem);
        $this->observacoes($section, $ordem);
        $this->rodape($section, $ordem);
        $this->assinatura($section);

        $temporary = tempnam(sys_get_temp_dir(), 'ordem-');
        if ($temporary === false) {
            throw new RuntimeException('Falha ao criar arquivo temporário.');
        }

        $path = 'ordens/'.$ordem->token.'/'.Str::uuid().'.docx';

        try {
            IOFactory::createWriter($doc, 'Word2007')->save($temporary);
            $stream = fopen($temporary, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Falha ao abrir documento.');
            }
            try {
                if (! Storage::disk('public')->put($path, $stream)) {
                    throw new RuntimeException('Falha ao armazenar documento.');
                }
            } finally {
                fclose($stream);
            }
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        } finally {
            @unlink($temporary);
        }

        return $path;
    }

    private function cabecalho($section, Empresa $empresa): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $table->addRow();
        $borda = ['borderBottomSize' => 12, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'single', 'valign' => 'center'];

        $logo = $table->addCell(2000, $borda);
        $identidade = $table->addCell(5500, $borda);
        $contato = $table->addCell(3800, $borda);

        $arquivo = public_path('img/logo-gold-press.png');
        if (is_file($arquivo)) {
            $logo->addImage($arquivo, ['width' => 90, 'height' => 25]);
        }
        $identidade->addText($empresa->nome, ['bold' => true, 'size' => 11]);
        if ($empresa->endereco) {
            $identidade->addText($empresa->endereco, ['size' => 7]);
        }

        $contato->addText($empresa->telefone ?: ' ', ['size' => 8], ['alignment' => Jc::RIGHT]);
        if ($empresa->documento) {
            $contato->addText('CNPJ '.$empresa->documento, ['size' => 8], ['alignment' => Jc::RIGHT]);
        }
        if ($empresa->site) {
            $contato->addText($empresa->site, ['size' => 7], ['alignment' => Jc::RIGHT]);
        }
    }

    private function titulo($section, OrdemServico $ordem): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => self::MARGEM_CELULA]);
        $table->addRow();
        $borda = ['borderBottomSize' => 8, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'single'];
        $table->addCell(7000, $borda)->addText('ORDEM DE SERVICO '.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT), ['bold' => true, 'size' => 11]);
        $table->addCell(4300, $borda)->addText('Hora: '.$ordem->created_at->format('H:i').'    Data: '.$ordem->created_at->format('d/m/Y'), ['size' => 8], ['alignment' => Jc::RIGHT]);
    }

    private function cliente($section, OrdemServico $ordem): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => self::MARGEM_CELULA]);
        $table->addRow();
        $table->addCell(1600)->addText('Cliente ..:', ['size' => 8]);
        $table->addCell(6200)->addText(mb_strtoupper((string) $ordem->nome), ['size' => 8, 'bold' => true]);
        $table->addCell(3500)->addText('CPF/Cnpj: '.($ordem->documento ?: ''), ['size' => 7.5]);

        $table->addRow();
        $table->addCell(1600)->addText('Endereço:', ['size' => 8]);
        $table->addCell(6200)->addText($this->endereco($ordem), ['size' => 8]);
        $table->addCell(3500)->addText('Cidade: '.($ordem->cidade ?: ''), ['size' => 7.5]);

        $table->addRow();
        $borda = ['borderBottomSize' => 8, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'single'];
        $table->addCell(7000, $borda)->addText('Bairro: '.($ordem->bairro ?: ''), ['size' => 7.5]);
        $table->addCell(1500, $borda)->addText('UF: '.($ordem->uf ?: ''), ['size' => 7.5]);
        $table->addCell(2800, $borda)->addText('CEP: '.($ordem->cep ?: ''), ['size' => 7.5]);
    }

    private function itens($section, OrdemServico $ordem): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => self::MARGEM_CELULA]);
        $larguras = [4000, 600, 900, 2500, 1100, 1100, 1100];
        $cabecalho = ['Descrição do Item', 'Uni', 'Quant', 'LAR x ALT = MT2', 'VL Metro', 'VL Unita', 'VL Total'];

        $table->addRow();
        foreach ($cabecalho as $indice => $texto) {
            $table->addCell($larguras[$indice], [
                'borderTopSize' => 8,
                'borderBottomSize' => 8,
                'borderTopColor' => '000000',
                'borderBottomColor' => '000000',
                'borderTopStyle' => 'single',
                'borderBottomStyle' => 'single',
            ])->addText($texto, ['size' => 8]);
        }

        $itens = $ordem->itens;
        if ($itens->isEmpty()) {
            $itens = collect([null]);
        }

        foreach ($itens as $indice => $item) {
            $zebra = $indice % 2 === 1 ? ['bgColor' => 'F2F2F2'] : [];
            $borda = [...$zebra, 'borderBottomSize' => 6, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'dotted'];
            $table->addRow();

            if ($item === null) {
                $table->addCell($larguras[0], $borda)->addText('—', ['size' => 8]);
                foreach (array_slice($larguras, 1) as $largura) {
                    $table->addCell($largura, $borda)->addText('', ['size' => 8]);
                }

                continue;
            }

            $porMetroQuadrado = $item->tipo->isMetroQuadrado();
            $medidas = $porMetroQuadrado
                ? number_format((float) $item->largura, 2, ',', '.').' x '.number_format((float) $item->altura, 2, ',', '.').' = '.number_format((float) $item->area, 4, ',', '.').' m²'
                : '';
            $vlMetro = $porMetroQuadrado ? $this->moeda($item->valor_m2) : '';
            $vlUnita = $porMetroQuadrado
                ? $this->moeda((float) $item->area * (float) $item->valor_m2)
                : $this->moeda($item->valor_unitario);

            $table->addCell($larguras[0], $borda)->addText($item->descricao, ['size' => 8]);
            $table->addCell($larguras[1], $borda)->addText($item->unidade ?: '', ['size' => 8]);
            $table->addCell($larguras[2], $borda)->addText(number_format((float) $item->quantidade, 3, ',', '.'), ['size' => 8], ['alignment' => Jc::RIGHT]);
            $table->addCell($larguras[3], $borda)->addText($medidas, ['size' => 8]);
            $table->addCell($larguras[4], $borda)->addText($vlMetro, ['size' => 8], ['alignment' => Jc::RIGHT]);
            $table->addCell($larguras[5], $borda)->addText($vlUnita, ['size' => 8], ['alignment' => Jc::RIGHT]);
            $table->addCell($larguras[6], $borda)->addText($this->moeda($item->subtotal), ['size' => 8], ['alignment' => Jc::RIGHT]);
        }
    }

    private function observacoes($section, OrdemServico $ordem): void
    {
        $section->addText('Observações Gerais:', ['bold' => true, 'size' => 8]);
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => self::MARGEM_CELULA]);
        $table->addRow();
        $table->addCell(self::LARGURA, ['borderBottomSize' => 8, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'single'])
            ->addText($ordem->observacoes ?: ' ', ['size' => 8]);
    }

    private function rodape($section, OrdemServico $ordem): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => self::MARGEM_CELULA]);
        $borda = ['borderBottomSize' => 8, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'single'];
        $linhasEsquerda = [
            'Responsavel' => $ordem->responsavel,
            'Situação Atual' => $ordem->status?->nome,
            'Datas' => 'Aprovação: '.($ordem->data_aprovacao?->format('d/m/Y') ?? '/ /').'    Entrega: '.($ordem->data_entrega?->format('d/m/Y') ?? '/ /'),
            'Condições de Pagamento' => $ordem->condicoes_pagamento,
        ];
        $linhasDireita = [
            'VALOR PRODUTOS' => $this->moeda($ordem->valor_produtos),
            'DESCONTO' => $this->moeda($ordem->desconto),
            'VALOR TOTAL' => $this->moeda($ordem->valor_total_final),
        ];

        $total = max(count($linhasEsquerda), count($linhasDireita));
        $offsetDireita = $total - count($linhasDireita);
        for ($i = 0; $i < $total; $i++) {
            $table->addRow();
            $esquerdaLabel = array_keys($linhasEsquerda)[$i] ?? '';
            $esquerdaValor = array_values($linhasEsquerda)[$i] ?? '';
            $indiceDireita = $i - $offsetDireita;
            $direitaLabel = $indiceDireita >= 0 ? (array_keys($linhasDireita)[$indiceDireita] ?? '') : '';
            $direitaValor = $indiceDireita >= 0 ? (array_values($linhasDireita)[$indiceDireita] ?? '') : '';
            $ultimo = $i === $total - 1;
            $estilo = $ultimo ? $borda : [];
            $negrito = $ultimo ? ['bold' => true] : [];

            $table->addCell(2200, $estilo)->addText($esquerdaLabel ? $esquerdaLabel.':' : '', ['size' => 8]);
            $table->addCell(3600, $estilo)->addText((string) ($esquerdaValor ?: ''), ['size' => 8]);
            $table->addCell(3200, [...$estilo, ...($ultimo ? ['borderTopSize' => 8, 'borderTopColor' => '000000', 'borderTopStyle' => 'single'] : [])])
                ->addText($direitaLabel, [...$negrito, 'size' => 8], ['alignment' => Jc::RIGHT]);
            $table->addCell(2300, [...$estilo, ...($ultimo ? ['borderTopSize' => 8, 'borderTopColor' => '000000', 'borderTopStyle' => 'single'] : [])])
                ->addText($direitaValor !== '' ? 'R$ '.$direitaValor : '', [...$negrito, 'size' => 8], ['alignment' => Jc::RIGHT]);
        }
    }

    private function assinatura($section): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $table->addRow();
        $table->addCell(7000)->addText('Impressão em 1 via - 1a VIA (X) - *** Obrigado pela Preferencia ***', ['size' => 8]);
        $table->addCell(4300)->addText('Visto ______________________________', ['size' => 8], ['alignment' => Jc::RIGHT]);
    }

    private function endereco(OrdemServico $ordem): string
    {
        $partes = array_filter([
            $ordem->endereco,
            $ordem->numero_endereco,
        ]);

        return implode(' - ', $partes);
    }

    private function moeda(mixed $valor): string
    {
        return number_format((float) $valor, 2, ',', '.');
    }
}
