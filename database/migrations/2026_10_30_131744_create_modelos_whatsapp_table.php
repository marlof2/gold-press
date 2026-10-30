<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modelos_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 80)->unique();
            $table->string('chave')->unique();
            $table->text('conteudo');
            $table->unsignedSmallInteger('posicao')->default(0);
            $table->timestamps();
        });
        $modelos = [
            'pedido' => ['Enviar ordem de serviço', '{abertura}'."\n\n*Ordem de serviço #{numero}*\n{itens}\n\n{financeiro}{prazo}\nConfira os itens e nos avise se precisar de algum ajuste.{fecho}"],
            'producao' => ['Pedido em produção', '{abertura}'."\n\nSeu pedido #{numero} está em produção. Avisaremos assim que estiver pronto!{prazo}{fecho}"],
            'retirada' => ['Pronto para retirada', '{abertura}'."\n\nSeu pedido #{numero} está pronto para retirada!{retirada}\n\n{financeiro}\nPodemos combinar a retirada por aqui?{fecho}"],
            'envio' => ['Pronto para envio', '{abertura}'."\n\nSeu pedido #{numero} está pronto para envio!\nPodemos confirmar o endereço e combinar a entrega por aqui?\n\n{financeiro}{fecho}"],
        ];
        $posicao = 0;
        foreach ($modelos as $chave => [$nome, $conteudo]) {
            DB::table('modelos_whatsapp')->insert(['chave' => $chave, 'nome' => $nome, 'conteudo' => $conteudo, 'posicao' => $posicao++, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['visualizar' => 'Visualizar mensagens', 'criar' => 'Cadastrar mensagens', 'editar' => 'Editar mensagens'] as $acao => $nome) {
            DB::table('permissoes')->insertOrIgnore(['nome' => $nome, 'slug' => 'mensagens-whatsapp.'.$acao, 'grupo' => 'mensagens-whatsapp', 'created_at' => now(), 'updated_at' => now()]);
            $id = DB::table('permissoes')->where('slug', 'mensagens-whatsapp.'.$acao)->value('id');
            foreach (DB::table('perfis')->where('slug', 'admin')->pluck('id') as $perfilId) {
                DB::table('perfil_permissao')->insertOrIgnore(['perfil_id' => $perfilId, 'permissao_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissoes')->where('grupo', 'mensagens-whatsapp')->delete();
        Schema::dropIfExists('modelos_whatsapp');
    }
};
