<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
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

        foreach (['endereco', 'numero_endereco', 'bairro', 'cidade', 'uf', 'cep'] as $campo) {
            if (! array_key_exists($campo, $dados)) {
                $dados[$campo] = $cliente->$campo;
            }
        }

        unset($dados['responsavel']);
        if (array_key_exists('responsavel_id', $dados)) {
            $responsavel = filled($dados['responsavel_id']) ? User::findOrFail($dados['responsavel_id']) : null;
            $dados['responsavel'] = $responsavel?->name ?? ($ordem?->responsavel_id === null ? $ordem?->responsavel : null);
        } elseif (! $ordem) {
            $dados['responsavel_id'] = auth()->id();
            $dados['responsavel'] = auth()->user()?->name;
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
        $sinal = (float) ($dados['sinal'] ?? 0);
        unset($dados['itens'], $dados['sinal']);

        $anterior = $ordem?->arquivo_path;
        $novo = null;

        try {
            $ordem = DB::transaction(function () use ($ordem, $dados, $itens, $sinal, &$novo) {
                if ($ordem) {
                    $ordem = OrdemServico::lockForUpdate()->findOrFail($ordem->id);
                }
                $pago = $ordem?->valorPago() ?? 0;
                if ((int) round(($pago + $sinal) * 100) > (int) round((float) $dados['valor_total_final'] * 100)) {
                    throw ValidationException::withMessages(['sinal' => 'O total da OS não pode ser menor que o valor já recebido e o sinal informado.']);
                }
                $ordem ??= new OrdemServico(['token' => (string) Str::uuid()]);
                $ordem->fill($dados)->save();
                $ordem->itens()->delete();
                foreach ($itens as $item) {
                    $ordem->itens()->create($item);
                }
                if ($sinal > 0) {
                    $ordem->pagamentos()->create(['chave' => (string) Str::uuid(), 'valor' => $sinal, 'data_pagamento' => today(), 'forma' => 'outro', 'observacoes' => 'Sinal registrado na criação da OS', 'user_id' => auth()->id()]);
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

    public function alterarStatus(OrdemServico $ordem, Status $status): void
    {
        $anterior = $ordem->arquivo_path;
        $novo = null;
        try {
            DB::transaction(function () use ($ordem, $status, &$novo): void {
                $ordem = OrdemServico::lockForUpdate()->findOrFail($ordem->id);
                $ordem->update(['status_id' => $status->id, 'situacao' => $status->nome]);
                $novo = $this->documento->gerar($ordem->fresh('itens'));
                $ordem->update(['arquivo_path' => $novo]);
            });
        } catch (Throwable $erro) {
            if ($novo) {
                Storage::disk('public')->delete($novo);
            }
            throw $erro;
        }
        if ($anterior && $anterior !== $novo) {
            Storage::disk('public')->delete($anterior);
        }
    }
}
