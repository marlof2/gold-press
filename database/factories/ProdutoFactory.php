<?php

namespace Database\Factories;

use App\Enums\TipoCobranca;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produto>
 */
class ProdutoFactory extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        $tipo = fake()->randomElement([TipoCobranca::Unitario, TipoCobranca::MetroQuadrado]);

        return [
            'nome' => fake()->unique()->words(2, true),
            'tipo' => $tipo,
            'unidade' => $tipo === TipoCobranca::MetroQuadrado ? 'm²' : 'un',
            'valor_unitario' => $tipo === TipoCobranca::Unitario ? fake()->randomFloat(2, 5, 100) : 0,
            'valor_m2' => $tipo === TipoCobranca::MetroQuadrado ? fake()->randomFloat(2, 50, 300) : 0,
            'ativo' => true,
        ];
    }

    public function unitario(): static
    {
        return $this->state(fn () => ['tipo' => TipoCobranca::Unitario, 'unidade' => 'un', 'valor_unitario' => 10, 'valor_m2' => 0]);
    }

    public function metroQuadrado(): static
    {
        return $this->state(fn () => ['tipo' => TipoCobranca::MetroQuadrado, 'unidade' => 'm²', 'valor_unitario' => 0, 'valor_m2' => 100]);
    }
}
