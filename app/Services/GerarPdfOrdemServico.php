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

        return $pdf->output();
    }
}
