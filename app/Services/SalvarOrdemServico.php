<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SalvarOrdemServico
{
    public function __construct(private CalcularOrdemServico $calculadora, private GerarDocumentoOrdemServico $documento) {}

    public function salvar(array $dados, ?OrdemServico $ordem = null): OrdemServico
    {
        $cliente = Cliente::findOrFail($dados['cliente_id']);
        $status = Status::findOrFail($dados['status_id']);
        $dados = $this->calculadora->calcular([...$dados, ...$cliente->only(Cliente::DADOS_OS), 'situacao' => $status->nome]);
        $anterior = $ordem?->arquivo_path;
        $novo = null;
        try {
            $ordem = DB::transaction(function () use ($ordem, $dados, &$novo) {
                $ordem ??= new OrdemServico(['token' => (string) Str::uuid()]);
                $ordem->fill($dados)->save();
                $novo = $this->documento->gerar($ordem);
                $ordem->update(['arquivo_path' => $novo]);

                return $ordem;
            });
        } catch (Throwable $e) {
            if ($novo) {
                Storage::disk('public')->delete($novo);
            } throw $e;
        }
        if ($anterior && $anterior !== $novo) {
            Storage::disk('public')->delete($anterior);
        }

        return $ordem;
    }
}
