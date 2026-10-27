<?php

namespace App\Http\Requests;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $usuario = $this->route('usuario');
            if ($usuario?->isAdmin() && ! Perfil::find($this->integer('perfil_id'))?->isAdmin() && User::whereHas('perfil', fn ($query) => $query->where('slug', Perfil::ADMIN))->count() <= 1) {
                $validator->errors()->add('perfil_id', 'Mantenha pelo menos um administrador no sistema.');
            }
        }];
    }
}
