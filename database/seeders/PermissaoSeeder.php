<?php

namespace Database\Seeders;

use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PermissaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('permissoes') as $grupo => $dados) {
            foreach ($dados['acoes'] as $acao => $nome) {
                Permissao::updateOrCreate(['slug' => $grupo.'.'.$acao], ['nome' => $nome, 'grupo' => $grupo]);
            }
        }
    }
}
