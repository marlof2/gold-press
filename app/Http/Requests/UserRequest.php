<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->email)),
            'password' => $this->filled('password') ? $this->password : null,
        ]);
    }

    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($usuario)],
            'perfil_id' => ['required', 'integer', 'exists:perfis,id'],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'confirmed'],
        ];
    }
}
