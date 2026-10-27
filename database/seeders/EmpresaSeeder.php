<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        if (Empresa::query()->exists()) {
            return;
        }

        Empresa::create([
            'nome' => 'Gold Press Digital',
            'razao_social' => 'Gold Press Digital',
            'documento' => '41.243.264/0001-53',
            'endereco' => 'Rua Orozí n- 47 - Fazenda grande do Re - Salvador-BA - 40353-165',
            'telefone' => '(71) 98641-4847',
        ]);
    }
}
