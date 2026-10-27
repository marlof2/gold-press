<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['finalizado' => $this->boolean('finalizado')]);
    }

    public function rules(): array
    {
        return ['nome' => ['required', 'string', 'max:80', Rule::unique('statuses')->ignore($this->route('status'))], 'cor' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'], 'posicao' => ['required', 'integer', 'min:0', 'max:999'], 'finalizado' => ['boolean']];
    }
}
