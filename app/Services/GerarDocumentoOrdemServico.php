<?php

namespace App\Services;

use App\Models\OrdemServico;
use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;

class GerarDocumentoOrdemServico
{
    private const LARGURA = 10800;

    public function gerar(OrdemServico $ordem): string
    {
        $dados = app(DadosDocumentoOrdemServico::class)->para($ordem);
        Settings::setOutputEscapingEnabled(true);
        $doc = new PhpWord;
        $doc->setDefaultFontName('Arial');
        $doc->setDefaultFontSize(8);
        $doc->setDefaultParagraphStyle(['spaceAfter' => 30, 'spaceBefore' => 0]);
        $section = $doc->addSection([
            'orientation' => 'portrait',
            'marginTop' => 480, 'marginBottom' => 825,
            'marginLeft' => 540, 'marginRight' => 540,
        ]);

        $this->cabecalho($section, $dados);
        $this->cliente($section, $dados);
        $this->itens($section, $dados);
        $this->financeiro($section, $dados);
        foreach ($dados['notas'] as $rotulo => $valor) {
            $this->titulo($section, $rotulo);
            foreach (preg_split('/\R/u', $valor) as $linha) {
                $section->addText($linha ?: ' ', ['color' => '27372F']);
            }
        }
        $this->assinatura($section);
        $footer = $section->addFooter();
        $footer->addPreserveText('OS #'.$dados['numero'].' · Gerado em '.now()->format('d/m/Y H:i').'     |     Página {PAGE} de {NUMPAGES}', ['size' => 7, 'color' => '65715F']);

        $temporario = tempnam(sys_get_temp_dir(), 'ordem-');
        if ($temporario === false) {
            throw new RuntimeException('Falha ao criar arquivo temporário.');
        }
        try {
            IOFactory::createWriter($doc, 'Word2007')->save($temporario);
            $conteudo = file_get_contents($temporario);
            if ($conteudo === false) {
                throw new RuntimeException('Falha ao ler documento gerado.');
            }

            return $conteudo;
        } finally {
            @unlink($temporario);
        }
    }

    /** @param array<string, mixed> $dados */
    private function cabecalho(Section $section, array $dados): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $table->addRow(null, ['cantSplit' => true]);
        $empresa = $table->addCell(7020, ['valign' => 'center']);
        if ($dados['logo_path']) {
            $empresa->addImage($dados['logo_path'], ['width' => 142.5, 'height' => 40.65]);
        } else {
            $empresa->addText($dados['empresa'], ['bold' => true, 'size' => 15]);
        }
        $contatos = $table->addCell(3780, ['valign' => 'center']);
        foreach (['Telefone' => $dados['empresa_telefone'], 'CNPJ' => $dados['empresa_documento']] as $rotulo => $valor) {
            if (filled($valor)) {
                $linha = $contatos->addTextRun(['alignment' => Jc::RIGHT]);
                $linha->addText($rotulo.': ', ['bold' => true, 'size' => 7.5]);
                $linha->addText($valor, ['size' => 7.5]);
            }
        }
        foreach ([$dados['empresa_endereco'], $dados['empresa_contatos']] as $linha) {
            if (filled($linha)) {
                $section->addText($linha, ['size' => 7, 'color' => '65715F']);
            }
        }
        $section->addText('ORDEM DE SERVIÇO #'.$dados['numero'], ['bold' => true, 'size' => 14, 'color' => '233B30'], ['alignment' => Jc::CENTER, 'spaceBefore' => 180, 'spaceAfter' => 100, 'keepNext' => true]);
        $faixa = $section->addTable(['borderSize' => 0, 'cellMargin' => 100]);
        $faixa->addRow(null, ['cantSplit' => true]);
        $faixa->addCell(self::LARGURA, ['bgColor' => 'D2691E'])->addText(mb_strtoupper($dados['status']), ['bold' => true, 'size' => 13, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER, 'keepNext' => true]);
        $section->addTextBreak(1, ['size' => 5]);
    }

    /** @param array<string, mixed> $dados */
    private function cliente(Section $section, array $dados): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'cellMarginBottom' => 90]);
        $table->addRow(null, ['cantSplit' => true]);
        $this->campo($table->addCell(6696), 'Cliente', $dados['cliente']);
        $this->campo($table->addCell(4104), $dados['documento_rotulo'], $dados['documento']);
        $table->addRow(null, ['cantSplit' => true]);
        $this->campo($table->addCell(6696), 'Telefone', $dados['telefone']);
        $this->campo($table->addCell(4104), 'Emissão', $dados['prazos']['Emissão']);
        $table->addRow(null, ['cantSplit' => true]);
        $this->campo($table->addCell(self::LARGURA, ['gridSpan' => 2]), 'Endereço de execução / entrega', $dados['endereco']);
        $table->addRow(null, ['cantSplit' => true]);
        $this->campo($table->addCell(6696), 'Responsável da gráfica', $dados['responsavel']);
        $this->campo($table->addCell(4104), 'Entrega prevista', $dados['prazos']['Entrega prevista']);
        if (isset($dados['prazos']['Aprovação'])) {
            $table->addRow(null, ['cantSplit' => true]);
            $this->campo($table->addCell(self::LARGURA, ['gridSpan' => 2]), 'Aprovação', $dados['prazos']['Aprovação']);
        }
    }

    /** @param array<string, mixed> $dados */
    private function itens(Section $section, array $dados): void
    {
        $this->titulo($section, 'Itens e serviços');
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 120]);
        $larguras = [3672, 1080, 2376, 1836, 1836];
        $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach (['Produto / serviço', 'Qtd.', 'Medidas', 'Preço', 'Subtotal'] as $indice => $rotulo) {
            $table->addCell($larguras[$indice], ['bgColor' => 'D2691E', 'borderBottomSize' => 4, 'borderBottomColor' => 'D2691E'])
                ->addText($rotulo, ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'], ['alignment' => $indice >= 3 ? Jc::RIGHT : Jc::LEFT]);
        }
        foreach ($dados['itens'] as $indice => $item) {
            $table->addRow(null, ['cantSplit' => true]);
            $borda = ['borderBottomSize' => 4, 'borderBottomColor' => 'E5E9E3', 'bgColor' => $indice % 2 === 0 ? 'FFFFFF' : 'FFF3E8'];
            $descricao = $table->addCell($larguras[0], $borda);
            $descricao->addText($item['descricao'], ['bold' => true, 'size' => 9, 'color' => '233B30']);
            foreach ($item['detalhes'] as $detalhe) {
                $descricao->addText($detalhe, ['size' => 7.5, 'color' => '65715F'], ['spaceBefore' => 40]);
            }
            $table->addCell($larguras[1], $borda)->addText($item['quantidade'], ['size' => 8]);
            $table->addCell($larguras[2], $borda)->addText($item['medidas'], ['size' => 8]);
            $preco = $table->addCell($larguras[3], $borda);
            $preco->addText($item['preco'], ['size' => 8], ['alignment' => Jc::RIGHT]);
            $preco->addText($item['preco_unidade'], ['size' => 7.5, 'color' => '65715F'], ['alignment' => Jc::RIGHT]);
            $table->addCell($larguras[4], $borda)->addText($item['subtotal'], ['size' => 8], ['alignment' => Jc::RIGHT]);
        }
    }

    /** @param array<string, mixed> $dados */
    private function financeiro(Section $section, array $dados): void
    {
        $section->addTextBreak(1, ['size' => 8]);
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 100]);
        foreach ($dados['totais'] as $rotulo => $valor) {
            if ($rotulo === 'Valor pago') {
                $section->addTextBreak(1, ['size' => 5]);
                $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 100]);
            }
            $table->addRow(null, ['cantSplit' => true]);
            $estilo = [
                'borderBottomSize' => 4, 'borderBottomColor' => 'E5E9E3',
                ...match ($rotulo) {
                    'Total da OS' => ['bgColor' => 'D2691E'],
                    'Valor a pagar' => ['bgColor' => 'EDF2ED'],
                    default => [],
                },
            ];
            $fonte = ['size' => $rotulo === 'Total da OS' ? 10 : 8, 'color' => $rotulo === 'Total da OS' ? 'FFFFFF' : '27372F'];
            $table->addCell(5400, $estilo)->addText($rotulo, $fonte);
            $table->addCell(5400, $estilo)->addText($valor, [...$fonte, 'bold' => true], ['alignment' => Jc::RIGHT]);
        }
    }

    private function campo(Cell $cell, string $rotulo, string $valor): void
    {
        $linha = $cell->addTextRun();
        $linha->addText($rotulo.': ', ['color' => '27372F']);
        $linha->addText($valor, ['bold' => true, 'color' => '27372F']);
    }

    private function assinatura(Section $section): void
    {
        $section->addTextBreak(1, ['size' => 12]);
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $table->addRow(null, ['cantSplit' => true]);
        $table->addCell(5400)->addText('Obrigado pela preferência!', ['bold' => true, 'color' => '27372F']);
        $table->addCell(5400)->addText('Visto _____________________________________', ['bold' => true, 'color' => '27372F'], ['alignment' => Jc::RIGHT]);
    }

    private function titulo(Section $section, string $titulo): void
    {
        $section->addText(mb_strtoupper($titulo), ['bold' => true, 'size' => 10, 'color' => '233B30'], ['spaceBefore' => 180, 'spaceAfter' => 90, 'keepNext' => true]);
    }
}
