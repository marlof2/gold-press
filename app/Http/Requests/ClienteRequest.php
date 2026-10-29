<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['telefone' => preg_replace('/\D/', '', (string) $this->telefone) ?: null, 'documento' => preg_replace('/\D/', '', (string) $this->documento) ?: null, 'uf' => strtoupper((string) $this->uf)]);
        if (! $this->has('tipo_pessoa')) {
            $this->merge(['tipo_pessoa' => $this->documento ? (strlen($this->documento) === 14 ? 'PJ' : 'PF') : ($this->route('cliente')?->tipo_pessoa ?? 'PF')]);
        }
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'tipo_pessoa' => ['required', Rule::in(['PF', 'PJ'])],
            'documento' => ['nullable', $this->input('tipo_pessoa') === 'PJ' ? 'regex:/^[0-9]{14}$/' : 'regex:/^[0-9]{11}$/', Rule::unique('clientes')->ignore($this->route('cliente'))],
            'telefone' => ['nullable', 'regex:/^(?:55)?[1-9][0-9]{9,10}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'numero_endereco' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:255'], 'cidade' => ['nullable', 'string', 'max:255'], 'bairro' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'regex:/^[A-Z]{2}$/'], 'cep' => ['nullable', 'string', 'max:10'], 'observacoes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return ['documento.regex' => $this->input('tipo_pessoa') === 'PJ' ? 'Informe um CNPJ com 14 dígitos para pessoa jurídica.' : 'Informe um CPF com 11 dígitos para pessoa física.'];
    }
}
