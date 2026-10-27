<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordens_servico', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();
            $table->string('documento')->nullable();
            $table->string('endereco')->nullable();
            $table->string('cidade')->nullable();
            $table->string('bairro')->nullable();
            $table->string('uf')->nullable();
            $table->string('cep')->nullable();
            $table->string('telefone')->nullable();
            $table->string('descricao')->nullable();
            $table->string('codigo')->nullable();
            $table->string('referencia')->nullable();
            $table->string('unidade')->nullable();
            $table->string('formato')->nullable();
            $table->string('vias')->nullable();
            $table->string('cores')->nullable();
            $table->string('complemento')->nullable();
            $table->string('responsavel')->nullable();
            $table->string('condicoes_pagamento')->nullable();
            $table->decimal('altura', 15, 4)->default(0);
            $table->decimal('largura', 15, 4)->default(0);
            $table->decimal('total_m2', 15, 4)->default(0);
            $table->decimal('valor_produto_m2', 15, 2)->default(0);
            $table->decimal('valor_total_m2', 15, 2)->default(0);
            $table->decimal('quantidade', 15, 4)->default(1);
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->decimal('arredondamento', 15, 2)->default(0);
            $table->decimal('acerto_valor', 15, 2)->default(0);
            $table->decimal('valor_produtos', 15, 2)->default(0);
            $table->decimal('valor_servicos', 15, 2)->default(0);
            $table->decimal('deslocamento', 15, 2)->default(0);
            $table->decimal('desconto', 15, 2)->default(0);
            $table->decimal('valor_total_final', 15, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->text('servico_realizado')->nullable();
            $table->string('situacao')->default('orcamento');
            $table->date('data_aprovacao')->nullable();
            $table->date('data_entrega')->nullable();
            $table->string('arquivo_path')->nullable();
            $table->uuid('token')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_servico');
    }
};
