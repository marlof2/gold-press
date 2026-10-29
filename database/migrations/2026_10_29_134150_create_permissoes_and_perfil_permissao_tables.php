<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('grupo');
            $table->timestamps();
        });
        Schema::create('perfil_permissao', function (Blueprint $table) {
            $table->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $table->foreignId('permissao_id')->constrained('permissoes')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['perfil_id', 'permissao_id']);
        });
        $agora = now();
        foreach (config('permissoes') as $grupo => $dados) {
            foreach ($dados['acoes'] as $acao => $nome) {
                $id = DB::table('permissoes')->insertGetId(['nome' => $nome, 'slug' => $grupo.'.'.$acao, 'grupo' => $grupo, 'created_at' => $agora, 'updated_at' => $agora]);
                foreach (DB::table('perfis')->pluck('id') as $perfilId) {
                    DB::table('perfil_permissao')->insert(['perfil_id' => $perfilId, 'permissao_id' => $id, 'created_at' => $agora, 'updated_at' => $agora]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perfil_permissao');
        Schema::dropIfExists('permissoes');
    }
};
