<?php

namespace App\Services;

use App\Models\Empresa;
use App\Models\OrdemServico;
use Dompdf\Dompdf;
use Dompdf\Options;

class GerarPdfOrdemServico
{
    public function gerar(OrdemServico $ordem): string
    {
        $ordem->loadMissing('itens', 'status', 'pagamentos');
        $pdf = new Dompdf(new Options(['defaultFont' => 'DejaVu Sans', 'isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'tempDir' => sys_get_temp_dir(), 'fontCache' => sys_get_temp_dir()]));
        $pdf->loadHtml(view('ordens.pdf', ['ordem' => $ordem, 'empresa' => Empresa::atual()])->render());
        $pdf->setPaper('A4');
        $pdf->render();

        $canvas = $pdf->getCanvas();
        $fonte = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $codigo = str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT);
        $canvas->page_text(27, 817, 'OS #'.$codigo.' · Gerado em '.now()->format('d/m/Y H:i'), $fonte, 7, [0.4, 0.45, 0.4]);
        $canvas->page_text(463, 817, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fonte, 7, [0.4, 0.45, 0.4]);

        return $pdf->output();
    }
}
