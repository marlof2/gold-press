<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PerfilSeeder extends Seeder
{
    public function run(): void
    {
        $perfis = [
            ['slug' => Perfil::ADMIN, 'nome' => 'Administrador', 'descricao' => 'Gerencia usuários, empresa e todos os registros da gráfica.'],
            ['slug' => Perfil::OPERADOR, 'nome' => 'Operador', 'descricao' => 'Opera ordens de serviço, clientes e status do dia a dia.'],
        ];

        foreach ($perfis as $perfil) {
            $registro = Perfil::updateOrCreate(['slug' => $perfil['slug']], $perfil);
            if ($registro->wasRecentlyCreated) {
                $registro->permissoes()->sync(Permissao::pluck('id'));
            }
        }
    }
}
