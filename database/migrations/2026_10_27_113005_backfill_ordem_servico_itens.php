<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ordens_servico')->orderBy('id')->each(function ($ordem) {
            if (DB::table('ordem_servico_itens')->where('ordem_servico_id', $ordem->id)->exists()) {
                return;
            }
            if (! $ordem->descricao && (float) $ordem->valor_total === 0.0) {
                return;
            }
            $porMetroQuadrado = (float) $ordem->largura > 0 && (float) $ordem->altura > 0;
            DB::table('ordem_servico_itens')->insert([
                'ordem_servico_id' => $ordem->id,
                'tipo' => $porMetroQuadrado ? 'm2' : 'unitario',
                'descricao' => $ordem->descricao ?: 'Item da OS #'.$ordem->id,
                'unidade' => $ordem->unidade,
                'codigo' => $ordem->codigo,
                'referencia' => $ordem->referencia,
                'formato' => $ordem->formato,
                'vias' => $ordem->vias,
                'cores' => $ordem->cores,
                'quantidade' => $ordem->quantidade ?: 1,
                'largura' => $porMetroQuadrado ? $ordem->largura : null,
                'altura' => $porMetroQuadrado ? $ordem->altura : null,
                'area' => $porMetroQuadrado ? $ordem->total_m2 : null,
                'valor_unitario' => $porMetroQuadrado ? 0 : $ordem->valor_total_m2,
                'valor_m2' => $porMetroQuadrado ? $ordem->valor_produto_m2 : 0,
                'subtotal' => $ordem->valor_total,
                'posicao' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('ordem_servico_itens')->truncate();
    }
};
