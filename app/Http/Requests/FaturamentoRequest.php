<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FaturamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('faturamento.visualizar') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'inicio' => $this->input('inicio') ?: now()->startOfYear()->toDateString(),
            'fim' => $this->input('fim') ?: now()->endOfYear()->toDateString(),
        ]);
    }

    public function rules(): array
    {
        return [
            'inicio' => ['required', 'date_format:Y-m-d', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'fim' => ['required', 'date_format:Y-m-d', 'after_or_equal:inicio', 'before_or_equal:2100-12-31'],
        ];
    }

    public function attributes(): array
    {
        return ['inicio' => 'data inicial', 'fim' => 'data final'];
    }
}
