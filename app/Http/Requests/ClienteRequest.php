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
        $this->merge(['telefone' => preg_replace('/\D/', '', (string) $this->telefone), 'documento' => preg_replace('/\D/', '', (string) $this->documento) ?: null, 'uf' => strtoupper((string) $this->uf)]);
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['nullable', 'regex:/^(?:[0-9]{11}|[0-9]{14})$/', Rule::unique('clientes')->ignore($this->route('cliente'))],
            'telefone' => ['required', 'regex:/^(?:55)?[1-9][0-9]{9,10}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'endereco' => ['nullable', 'string', 'max:255'], 'cidade' => ['nullable', 'string', 'max:255'], 'bairro' => ['nullable', 'string', 'max:255'],
            'uf' => ['nullable', 'regex:/^[A-Z]{2}$/'], 'cep' => ['nullable', 'string', 'max:10'], 'observacoes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
