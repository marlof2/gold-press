<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Services\GerarDocumentoOrdemServico;
use App\Services\GerarPdfOrdemServico;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocumentoOrdemController extends Controller
{
    public function show(Request $request, string $token, string $formato, GerarPdfOrdemServico $pdf, GerarDocumentoOrdemServico $word): Response
    {
        $ordem = OrdemServico::where('token', $token)->firstOrFail();
        $nome = 'OS-'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT).'.'.$formato;
        if ($formato === 'pdf') {
            return response($pdf->gerar($ordem), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$nome.'"',
                'Cache-Control' => 'private, no-store',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        return response($word->gerar($ordem), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="'.$nome.'"',
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }
}
