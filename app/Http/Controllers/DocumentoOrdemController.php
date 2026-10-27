<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Services\GerarPdfOrdemServico;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentoOrdemController extends Controller
{
    public function show(Request $request, string $token, string $formato, GerarPdfOrdemServico $pdf): Response
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
        abort_unless($ordem->arquivo_path && Storage::disk('public')->exists($ordem->arquivo_path), 404);

        return Storage::disk('public')->download($ordem->arquivo_path, $nome, ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow']);
    }
}
