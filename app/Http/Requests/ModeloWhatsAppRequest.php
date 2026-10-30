<?php

namespace App\Http\Requests;

use App\Services\MensagensWhatsApp;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModeloWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:80', Rule::unique('modelos_whatsapp')->ignore($this->route('modelo'))],
            'conteudo' => ['bail', 'required', 'string', 'max:6000', function (string $attribute, mixed $value, Closure $fail): void {
                preg_match_all('/\{([^{}\r\n]+)\}/u', $value, $matches);
                foreach ($matches[1] as $variavel) {
                    if (! array_key_exists($variavel, MensagensWhatsApp::VARIAVEIS)) {
                        $fail('O campo {'.$variavel.'} não existe. Use os campos disponíveis abaixo.');
                    }
                }
            }],
            'posicao' => ['required', 'integer', 'min:0', 'max:999'],
        ];
    }

    public function attributes(): array
    {
        return ['conteudo' => 'texto da mensagem', 'posicao' => 'posição'];
    }
}
