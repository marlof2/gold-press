<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use App\Models\Pagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PagamentoController extends Controller
{
    public function store(Request $request, OrdemServico $ordem): RedirectResponse|JsonResponse
    {
        $valor = trim((string) $request->input('valor'));
        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }
        $request->merge(['valor' => preg_replace('/[^0-9.\-]/', '', $valor)]);
        $dados = $request->validate([
            'chave' => ['required', 'uuid'], 'valor' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:1000000'],
            'data_pagamento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'forma' => ['required', Rule::in(['pix', 'dinheiro', 'cartao', 'transferencia', 'outro'])],
            'observacoes' => ['nullable', 'string', 'max:500'],
        ]);
        DB::transaction(function () use ($ordem, $dados, $request): void {
            $ordem = OrdemServico::lockForUpdate()->findOrFail($ordem->id);
            $existente = Pagamento::where('chave', $dados['chave'])->first();
            if ($existente) {
                if ($existente->ordem_servico_id !== $ordem->id) {
                    throw ValidationException::withMessages(['chave' => 'Atualize a página e tente novamente.']);
                }

                return;
            }
            if ((int) round((float) $dados['valor'] * 100) > (int) round($ordem->saldoRestante() * 100)) {
                throw ValidationException::withMessages(['valor' => 'O pagamento não pode ultrapassar o valor a pagar da OS.']);
            }
            $ordem->pagamentos()->create([...$dados, 'user_id' => $request->user()->id]);
        });

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Pagamento registrado. O saldo da OS foi atualizado.');

            return response()->json(['message' => 'Pagamento registrado. O saldo da OS foi atualizado.', 'valor_pago' => $ordem->valorPago()]);
        }

        return back()->with('success', 'Pagamento registrado. O saldo da OS foi atualizado.');
    }

    public function estornar(Request $request, OrdemServico $ordem, Pagamento $pagamento): RedirectResponse|JsonResponse
    {
        abort_unless($pagamento->ordem_servico_id === $ordem->id, 404);
        $dados = $request->validate(['motivo_estorno' => ['required', 'string', 'max:255']]);
        $pagamento->forceFill(['estornado_em' => $pagamento->estornado_em ?? now(), ...$dados])->save();

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Pagamento estornado. O registro foi mantido no histórico.');

            return response()->json(['message' => 'Pagamento estornado. O registro foi mantido no histórico.', 'valor_pago' => $ordem->valorPago()]);
        }

        return back()->with('success', 'Pagamento estornado. O registro foi mantido no histórico.');
    }
}
