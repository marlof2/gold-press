<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $id = $this->input('cliente_id');
        $cliente = is_scalar($id) && ctype_digit((string) $id) ? Cliente::find($id) : null;
        if ($cliente) {
            $this->merge($cliente->only(Cliente::DADOS_OS));
        }
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:255'],
            'codigo' => ['nullable', 'string', 'max:255'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'unidade' => ['nullable', 'string', 'max:255'],
            'formato' => ['nullable', 'string', 'max:255'],
            'vias' => ['nullable', 'string', 'max:255'],
            'cores' => ['nullable', 'string', 'max:255'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'responsavel' => ['nullable', 'string', 'max:255'],
            'condicoes_pagamento' => ['nullable', 'string', 'max:255'],
            'telefone' => ['required', 'regex:/^(?:55)?[1-9][0-9]{9,10}$/'],
            'uf' => ['nullable', 'regex:/^[A-Za-z]{2}$/'],
            'altura' => ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:10000'],
            'largura' => ['nullable', 'numeric', 'decimal:0,4', 'min:0', 'max:10000'],
            'valor_produto_m2' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'quantidade' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:10000'],
            'arredondamento' => ['nullable', 'numeric', 'decimal:0,2', 'min:-1000000', 'max:1000000'],
            'acerto_valor' => ['nullable', 'numeric', 'decimal:0,2', 'min:-1000000', 'max:1000000'],
            'valor_servicos' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'deslocamento' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'desconto' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'observacoes' => ['nullable', 'string', 'max:10000'],
            'servico_realizado' => ['nullable', 'string', 'max:10000'],
            'data_aprovacao' => ['nullable', 'date_format:Y-m-d'],
            'data_entrega' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('data_aprovacao'), 'after_or_equal:data_aprovacao')],
        ];
    }

    public function attributes(): array
    {
        return ['nome' => 'nome do cliente', 'descricao' => 'descrição', 'telefone' => 'telefone com DDD'];
    }
}
