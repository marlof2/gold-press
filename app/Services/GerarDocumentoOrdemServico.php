<?php

namespace App\Services;

use App\Models\OrdemServico;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use RuntimeException;

class GerarDocumentoOrdemServico
{
    public function gerar(OrdemServico $ordem): string
    {
        Settings::setOutputEscapingEnabled(true);
        $doc = new PhpWord;
        $doc->setDefaultFontName('Calibri');
        $doc->setDefaultFontSize(10);
        $doc->addTitleStyle(1, ['bold' => true, 'size' => 14, 'color' => 'B08735']);
        $section = $doc->addSection(['marginTop' => 900, 'marginBottom' => 900]);
        $header = $section->addHeader();
        $header->addText(config('grafica.nome'), ['bold' => true, 'size' => 20, 'color' => 'B08735']);
        $header->addText(config('grafica.endereco').' | '.config('grafica.telefone'));
        $section->addTitle('ORDEM DE SERVIÇO #'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT), 1);
        $section->addText('Emissão: '.$ordem->created_at->format('d/m/Y H:i'));
        $grupos = [
            'Cliente' => ['nome', 'documento', 'endereco', 'cidade', 'bairro', 'uf', 'cep', 'telefone'],
            'Produto' => ['descricao', 'codigo', 'referencia', 'unidade', 'formato', 'vias', 'cores', 'complemento'],
            'Medidas e valores' => ['altura', 'largura', 'total_m2', 'valor_produto_m2', 'valor_total_m2', 'quantidade', 'valor_total', 'arredondamento', 'acerto_valor'],
            'Execução' => ['observacoes', 'servico_realizado', 'responsavel', 'situacao', 'data_aprovacao', 'data_entrega', 'condicoes_pagamento'],
            'Fechamento' => ['valor_produtos', 'valor_servicos', 'deslocamento', 'desconto', 'valor_total_final'],
        ];
        foreach ($grupos as $titulo => $campos) {
            $section->addTitle($titulo, 1);
            $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'DDDDDD', 'cellMargin' => 80]);
            foreach ($campos as $campo) {
                $valor = $ordem->$campo;
                if (str_starts_with($campo, 'data_')) {
                    $valor = $valor?->format('d/m/Y');
                } elseif (in_array($campo, ['altura', 'largura', 'total_m2', 'quantidade'])) {
                    $valor = number_format((float) $valor, 4, ',', '.');
                } elseif (in_array($campo, ['arredondamento', 'acerto_valor', 'deslocamento', 'desconto']) || str_starts_with($campo, 'valor_')) {
                    $valor = 'R$ '.number_format((float) $valor, 2, ',', '.');
                }
                $table->addRow();
                $table->addCell(3300, ['bgColor' => 'F4F2EC'])->addText(ucfirst(str_replace('_', ' ', $campo)), ['bold' => true]);
                $table->addCell(6000)->addText((string) ($valor ?? '—'));
            }
        }
        $section->addFooter()->addText('Documento gerado por '.config('grafica.nome'));
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
}
