<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            $this->merge([
                'nome' => $cliente->nome,
                'documento' => $cliente->documento,
                'telefone' => $cliente->telefone,
            ]);
        }

        if ($this->filled('uf')) {
            $this->merge(['uf' => strtoupper((string) $this->input('uf'))]);
        }

        foreach (['desconto', 'sinal'] as $campo) {
            if ($campo === 'sinal' && ! $this->has('sinal')) {
                continue;
            }
            $this->merge([$campo => $this->normalizarValor($this->input($campo))]);
        }

        $itens = $this->input('itens');
        if (is_array($itens)) {
            foreach ($itens as $indice => $item) {
                foreach (['quantidade', 'largura', 'altura', 'valor_unitario', 'valor_m2'] as $campo) {
                    $itens[$indice][$campo] = $this->normalizarValor($item[$campo] ?? null);
                }
            }
            $this->merge(['itens' => $itens]);
        }
    }

    public function rules(): array
    {
        return [
            'acao' => ['nullable', 'string', Rule::in(['salvar', 'salvar_pdf', 'salvar_docx', 'salvar_whatsapp'])],
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'status_id' => ['required', 'integer', 'exists:statuses,id'],
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'string', 'max:255'],
            'telefone' => ['required', 'regex:/^(?:55)?[1-9][0-9]{9,10}$/'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'numero_endereco' => ['nullable', 'string', 'max:20'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'regex:/^[A-Za-z]{2}$/'],
            'cep' => ['nullable', 'string', 'max:10'],
            'responsavel_id' => ['nullable', 'integer', 'exists:users,id'],
            'condicoes_pagamento' => ['nullable', 'string', 'max:255'],
            'data_aprovacao' => ['nullable', 'date_format:Y-m-d'],
            'data_entrega' => ['nullable', 'date_format:Y-m-d', Rule::when($this->filled('data_aprovacao'), 'after_or_equal:data_aprovacao')],
            'sinal' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000', Rule::prohibitedIf($this->route('ordem') !== null)],
            'desconto' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'observacoes' => ['nullable', 'string', 'max:10000'],
            'servico_realizado' => ['nullable', 'string', 'max:10000'],
            'itens' => ['required', 'array', 'min:1', 'max:50'],
            'itens.*.produto_id' => ['nullable', 'integer', 'exists:produtos,id'],
            'itens.*.tipo' => ['required', 'in:unitario,m2'],
            'itens.*.descricao' => ['required', 'string', 'max:255'],
            'itens.*.unidade' => ['nullable', 'string', 'max:20'],
            'itens.*.codigo' => ['nullable', 'string', 'max:255'],
            'itens.*.referencia' => ['nullable', 'string', 'max:255'],
            'itens.*.formato' => ['nullable', 'string', 'max:255'],
            'itens.*.vias' => ['nullable', 'string', 'max:255'],
            'itens.*.cores' => ['nullable', 'string', 'max:255'],
            'itens.*.quantidade' => ['required', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:10000'],
            'itens.*.largura' => ['nullable', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:10000'],
            'itens.*.altura' => ['nullable', 'numeric', 'decimal:0,4', 'min:0.0001', 'max:10000'],
            'itens.*.valor_unitario' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'itens.*.valor_m2' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            foreach ((array) $this->input('itens', []) as $indice => $item) {
                $tipo = $item['tipo'] ?? null;

                if ($tipo === 'unitario' && blank($item['valor_unitario'] ?? null)) {
                    $validator->errors()->add('itens.'.$indice.'.valor_unitario', 'Informe o valor unitário.');
                }

                if ($tipo === 'm2') {
                    foreach (['largura', 'altura', 'valor_m2'] as $campo) {
                        if (blank($item[$campo] ?? null)) {
                            $validator->errors()->add('itens.'.$indice.'.'.$campo, 'Obrigatório para itens por metro quadrado.');
                        }
                    }
                }
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'nome' => 'nome do cliente',
            'responsavel_id' => 'responsável da gráfica',
            'telefone' => 'telefone com DDD',
            'itens' => 'itens da OS',
            'itens.*.descricao' => 'descrição do item',
            'itens.*.quantidade' => 'quantidade',
            'itens.*.valor_unitario' => 'valor unitário',
            'itens.*.valor_m2' => 'valor por m²',
            'itens.*.largura' => 'largura',
            'itens.*.altura' => 'altura',
        ];
    }

    private function normalizarValor(mixed $valor): mixed
    {
        if (! is_string($valor)) {
            return $valor;
        }

        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        if (str_contains($valor, ',')) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }

        $valor = preg_replace('/[^0-9.\-]/', '', $valor);

        return $valor === '' ? null : $valor;
    }
}
