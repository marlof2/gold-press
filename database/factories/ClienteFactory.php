<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return ['nome' => fake()->name(), 'telefone' => '11999991234', 'email' => fake()->safeEmail(), 'cidade' => 'São Paulo', 'uf' => 'SP'];
    }
}
