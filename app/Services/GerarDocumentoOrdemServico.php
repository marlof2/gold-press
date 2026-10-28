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
        $doc->setDefaultFontSize(9);
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
        $borda = ['borderBottomSize' => 12, 'borderBottomColor' => 'B28D44'];
        $empresa = $table->addCell(7020, $borda);
        if ($dados['logo_path']) {
            $empresa->addImage($dados['logo_path'], ['width' => 150, 'height' => 42.8]);
        }
        foreach ($dados['empresa_linhas'] as $linha) {
            $empresa->addText($linha, ['size' => 7.5, 'color' => '65715F']);
        }
        $ordem = $table->addCell(3780, $borda);
        $ordem->addText('ORDEM DE SERVIÇO', ['bold' => true, 'size' => 9, 'color' => '233B30'], ['alignment' => Jc::RIGHT]);
        $ordem->addText('#'.$dados['numero'], ['bold' => true, 'size' => 22, 'color' => '233B30'], ['alignment' => Jc::RIGHT, 'spaceAfter' => 80]);
        $ordem->addText($dados['status'], ['size' => 7, 'color' => '65715F'], ['alignment' => Jc::RIGHT]);
        $section->addTextBreak(1, ['size' => 8]);
    }

    /** @param array<string, mixed> $dados */
    private function cliente(Section $section, array $dados): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 150]);
        $table->addRow(null, ['cantSplit' => true]);
        $cliente = $table->addCell(7344, ['bgColor' => 'F5F7F3']);
        $this->rotulo($cliente, 'Cliente');
        $cliente->addText($dados['cliente'], ['bold' => true, 'size' => 10, 'color' => '27372F'], ['spaceAfter' => 60]);
        $cliente->addText('CPF / CNPJ: '.$dados['documento']);
        $cliente->addText('Telefone: '.$dados['telefone']);
        $this->rotulo($cliente, 'Endereço de execução / entrega', 100);
        $cliente->addText($dados['endereco']);
        $prazos = $table->addCell(3456, ['bgColor' => 'F5F7F3', 'borderLeftSize' => 6, 'borderLeftColor' => 'B28D44']);
        foreach ($dados['prazos'] as $rotulo => $valor) {
            $this->rotulo($prazos, $rotulo);
            $prazos->addText($valor, [], ['spaceAfter' => 90]);
        }
        $section->addText('Responsável da gráfica: '.$dados['responsavel'], ['color' => '27372F'], ['spaceBefore' => 100]);
    }

    /** @param array<string, mixed> $dados */
    private function itens(Section $section, array $dados): void
    {
        $this->titulo($section, 'Itens e serviços');
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 120]);
        $larguras = [3672, 1080, 2376, 1836, 1836];
        $table->addRow(null, ['tblHeader' => true, 'cantSplit' => true]);
        foreach (['Produto / serviço', 'Qtd.', 'Medidas', 'Preço', 'Subtotal'] as $indice => $rotulo) {
            $table->addCell($larguras[$indice], ['bgColor' => '233B30', 'borderBottomSize' => 8, 'borderBottomColor' => 'B28D44'])
                ->addText($rotulo, ['bold' => true, 'size' => 8, 'color' => 'FFFFFF'], ['alignment' => $indice >= 3 ? Jc::RIGHT : Jc::LEFT]);
        }
        foreach ($dados['itens'] as $indice => $item) {
            $table->addRow(null, ['cantSplit' => true]);
            $borda = ['borderBottomSize' => 4, 'borderBottomColor' => 'E5E9E3', 'bgColor' => $indice % 2 === 0 ? 'FFFFFF' : 'F5F7F3'];
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
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 110]);
        foreach ($dados['totais'] as $rotulo => $valor) {
            $table->addRow(null, ['cantSplit' => true]);
            $table->addCell(5724)->addText('');
            $estilo = match ($rotulo) {
                'Total da OS' => ['bgColor' => '233B30', 'borderTopSize' => 8, 'borderTopColor' => 'B28D44'],
                'Saldo restante' => ['bgColor' => 'F5EEDC', 'borderBottomSize' => 6, 'borderBottomColor' => 'B28D44'],
                default => [],
            };
            $fonte = ['size' => in_array($rotulo, ['Total da OS', 'Saldo restante'], true) ? 11 : 9, 'color' => $rotulo === 'Total da OS' ? 'FFFFFF' : '233B30'];
            $table->addCell(2540, $estilo)->addText($rotulo, $fonte);
            $table->addCell(2536, $estilo)->addText($valor, [...$fonte, 'bold' => true], ['alignment' => Jc::RIGHT]);
        }
    }

    private function rotulo(Cell $cell, string $rotulo, int $espaco = 0): void
    {
        $cell->addText(mb_strtoupper($rotulo), ['size' => 7.5, 'color' => '65715F'], ['spaceBefore' => $espaco]);
    }

    private function titulo(Section $section, string $titulo): void
    {
        $section->addText(mb_strtoupper($titulo), ['bold' => true, 'size' => 10, 'color' => '233B30'], ['spaceBefore' => 220, 'spaceAfter' => 110, 'keepNext' => true]);
    }
}
