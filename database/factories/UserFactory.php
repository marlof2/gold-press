<?php

namespace Database\Factories;

use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'perfil_id' => fn () => $this->perfil(Perfil::OPERADOR, 'Operador'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function admin(): static
    {
        return $this->state(fn () => ['perfil_id' => $this->perfil(Perfil::ADMIN, 'Administrador')]);
    }

    public function operador(): static
    {
        return $this->state(fn () => ['perfil_id' => $this->perfil(Perfil::OPERADOR, 'Operador')]);
    }

    private function perfil(string $slug, string $nome): int
    {
        $perfil = Perfil::firstOrCreate(['slug' => $slug], ['nome' => $nome]);
        if ($perfil->wasRecentlyCreated) {
            $perfil->permissoes()->sync(Permissao::pluck('id'));
        }

        return $perfil->id;
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
