<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $perfil = Perfil::where('slug', Perfil::ADMIN)->firstOrFail();
        $email = 'marlosilva.f2@gmail.com';
        $existente = User::where('email', $email)->first();

        if ($existente) {
            if (! $existente->perfil_id) {
                $existente->update(['perfil_id' => $perfil->id]);
            }

            return;
        }

        $senha = '12345678';
        User::create([
            'name' => 'Desenvolvedor',
            'email' => $email,
            'password' => $senha,
            'perfil_id' => $perfil->id,
        ]);
        User::create([
            'name' => 'Ana Estela',
            'email' => 'gold@gmail.com',
            'password' => $senha,
            'perfil_id' => $perfil->id,
        ]);

        $this->command?->warn('Usuário administrador criado: '.$email);
        $this->command?->warn('Senha padrão: '.$senha);
        $this->command?->warn('Altere a senha em Minha conta após o primeiro acesso.');
    }
}
