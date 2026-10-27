<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SalvarOrdemServico
{
    public function __construct(private CalcularOrdemServico $calculadora, private GerarDocumentoOrdemServico $documento) {}

    /**
     * @param  array<string, mixed>  $dados
     */
    public function salvar(array $dados, ?OrdemServico $ordem = null): OrdemServico
    {
        $cliente = Cliente::findOrFail($dados['cliente_id']);
        $status = Status::findOrFail($dados['status_id']);
        $itens = $this->calculadora->itens($dados['itens'] ?? []);

        if ($itens === []) {
            throw ValidationException::withMessages(['itens' => 'Adicione pelo menos um item à OS.']);
        }

        $totais = $this->calculadora->totais($itens, $dados);

        foreach (['endereco', 'bairro', 'cidade', 'uf', 'cep'] as $campo) {
            if (blank($dados[$campo] ?? null)) {
                $dados[$campo] = $cliente->$campo;
            }
        }

        $dados = [
            ...$dados,
            'nome' => $cliente->nome,
            'documento' => $cliente->documento,
            'telefone' => $cliente->telefone,
            'numero_endereco' => $dados['numero_endereco'] ?? null,
            'descricao' => $dados['descricao'] ?? $itens[0]['descricao'],
            'situacao' => $status->nome,
            ...$totais,
        ];
        unset($dados['itens']);

        $anterior = $ordem?->arquivo_path;
        $novo = null;

        try {
            $ordem = DB::transaction(function () use ($ordem, $dados, $itens, &$novo) {
                $ordem ??= new OrdemServico(['token' => (string) Str::uuid()]);
                $ordem->fill($dados)->save();
                $ordem->itens()->delete();
                foreach ($itens as $item) {
                    $ordem->itens()->create($item);
                }
                $novo = $this->documento->gerar($ordem->fresh('itens'));
                $ordem->update(['arquivo_path' => $novo]);

                return $ordem;
            });
        } catch (Throwable $e) {
            if ($novo) {
                Storage::disk('public')->delete($novo);
            }
            throw $e;
        }

        if ($anterior && $anterior !== $novo) {
            Storage::disk('public')->delete($anterior);
        }

        return $ordem;
    }
}
