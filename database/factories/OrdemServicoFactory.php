<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrdemServico>
 */
class OrdemServicoFactory extends Factory
{
    protected $model = OrdemServico::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'status_id' => Status::factory(),
            'nome' => fake()->name(),
            'telefone' => '11999991234',
            'descricao' => fake()->sentence(3),
            'token' => (string) Str::uuid(),
            'situacao' => 'orcamento',
        ];
    }
}
