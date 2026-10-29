<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaturamentoRequest;
use App\Services\ConsultarFaturamento;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;

class FaturamentoController extends Controller
{
    public function __invoke(FaturamentoRequest $request, ConsultarFaturamento $consulta): View
    {
        $dados = $request->validated();
        $inicio = CarbonImmutable::parse($dados['inicio'])->startOfDay();
        $fim = CarbonImmutable::parse($dados['fim'])->startOfDay();

        return view('faturamento.index', [
            'inicio' => $inicio,
            'fim' => $fim,
            ...$consulta->consultar($inicio, $fim),
        ]);
    }
}
