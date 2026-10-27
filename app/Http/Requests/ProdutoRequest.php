<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['ativo' => $this->boolean('ativo')]);

        foreach (['valor_unitario', 'valor_m2'] as $campo) {
            $valor = $this->input($campo);
            if (is_string($valor)) {
                $valor = trim($valor);
                if (str_contains($valor, ',')) {
                    $valor = str_replace(['.', ','], ['', '.'], $valor);
                }
                $this->merge([$campo => preg_replace('/[^0-9.\-]/', '', $valor) ?: null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255', Rule::unique('produtos')->ignore($this->route('produto'))],
            'tipo' => ['required', 'in:unitario,m2'],
            'unidade' => ['nullable', 'string', 'max:20'],
            'valor_unitario' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'valor_m2' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:1000000'],
            'ativo' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('tipo') === 'unitario' && blank($this->input('valor_unitario'))) {
                $validator->errors()->add('valor_unitario', 'Informe o valor unitário do produto.');
            }
            if ($this->input('tipo') === 'm2' && blank($this->input('valor_m2'))) {
                $validator->errors()->add('valor_m2', 'Informe o valor por m² do produto.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['nome' => 'nome do produto', 'tipo' => 'tipo de cobrança', 'valor_unitario' => 'valor unitário', 'valor_m2' => 'valor por m²'];
    }
}
