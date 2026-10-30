<?php

namespace Database\Factories;

use App\Models\ModeloWhatsApp;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ModeloWhatsApp> */
class ModeloWhatsAppFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->sentence(3),
            'chave' => 'modelo-'.fake()->uuid(),
            'conteudo' => 'Olá, {cliente}! Seu pedido #{numero} está em andamento.',
            'posicao' => 10,
        ];
    }
}
