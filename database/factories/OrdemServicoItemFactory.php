<?php

namespace Database\Factories;

use App\Enums\TipoCobranca;
use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdemServicoItem>
 */
class OrdemServicoItemFactory extends Factory
{
    protected $model = OrdemServicoItem::class;

    public function definition(): array
    {
        return [
            'ordem_servico_id' => OrdemServico::factory(),
            'tipo' => TipoCobranca::Unitario,
            'descricao' => fake()->sentence(3),
            'quantidade' => 1,
            'valor_unitario' => 10,
            'valor_m2' => 0,
            'subtotal' => 10,
            'posicao' => 0,
        ];
    }

    public function metroQuadrado(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoCobranca::MetroQuadrado,
            'largura' => 1.5,
            'altura' => 2,
            'area' => 3,
            'valor_unitario' => 0,
            'valor_m2' => 50,
            'subtotal' => 150,
        ]);
    }
}
