<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('perfil')) ?? false;
    }

    public function rules(): array
    {
        return ['permissoes' => ['present', 'array'], 'permissoes.*' => ['required', 'integer', 'distinct', 'exists:permissoes,id']];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('permissoes')) {
            $this->merge(['permissoes' => []]);
        }
    }
}
