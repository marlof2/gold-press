<?php

namespace Database\Seeders;

use App\Enums\TipoCobranca;
use App\Models\Produto;
use Illuminate\Database\Seeder;

class ProdutoSeeder extends Seeder
{
    public function run(): void
    {
        $produtos = [
            ['nome' => 'Plotagem', 'tipo' => TipoCobranca::MetroQuadrado, 'unidade' => 'm²', 'valor_unitario' => 0, 'valor_m2' => 180],
            ['nome' => 'Banner em lona', 'tipo' => TipoCobranca::MetroQuadrado, 'unidade' => 'm²', 'valor_unitario' => 0, 'valor_m2' => 120],
            ['nome' => 'Adesivo vinil', 'tipo' => TipoCobranca::MetroQuadrado, 'unidade' => 'm²', 'valor_unitario' => 0, 'valor_m2' => 90],
            ['nome' => 'Impressão A4', 'tipo' => TipoCobranca::Unitario, 'unidade' => 'un', 'valor_unitario' => 5, 'valor_m2' => 0],
            ['nome' => 'Encadernação', 'tipo' => TipoCobranca::Unitario, 'unidade' => 'un', 'valor_unitario' => 15, 'valor_m2' => 0],
        ];

        foreach ($produtos as $produto) {
            Produto::updateOrCreate(['nome' => $produto['nome']], [...$produto, 'ativo' => true]);
        }
    }
}
