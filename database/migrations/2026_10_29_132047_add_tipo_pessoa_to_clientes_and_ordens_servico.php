<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['clientes', 'ordens_servico'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->string('tipo_pessoa', 2)->default('PF');
            });
            DB::table($tabela)->select('id', 'documento')->orderBy('id')->chunkById(200, function ($registros) use ($tabela) {
                foreach ($registros as $registro) {
                    if (strlen(preg_replace('/\D/', '', (string) $registro->documento)) === 14) {
                        DB::table($tabela)->where('id', $registro->id)->update(['tipo_pessoa' => 'PJ']);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['clientes', 'ordens_servico'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn('tipo_pessoa');
            });
        }
    }
};
