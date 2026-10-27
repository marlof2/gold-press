<?php

namespace Database\Factories;

use App\Models\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return ['nome' => fake()->unique()->words(3, true), 'slug' => (string) Str::uuid(), 'cor' => '#2563eb', 'posicao' => 10, 'finalizado' => false];
    }
}
