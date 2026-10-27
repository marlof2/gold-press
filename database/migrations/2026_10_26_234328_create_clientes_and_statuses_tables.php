<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('documento')->nullable()->unique();
            foreach (['endereco', 'cidade', 'bairro', 'uf', 'cep'] as $campo) {
                $table->string($campo)->nullable();
            }
            $table->string('telefone');
            $table->string('email')->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('slug')->unique();
            $table->string('cor', 7)->default('#64748b');
            $table->unsignedInteger('posicao')->default(0);
            $table->boolean('finalizado')->default(false);
            $table->timestamps();
        });
        $defaults = [['Orçamento', 'orcamento', '#b08735', false], ['Aprovada', 'aprovada', '#2563eb', false], ['Em produção', 'producao', '#9333ea', false], ['Concluída', 'concluida', '#15803d', true], ['Cancelada', 'cancelada', '#b91c1c', true]];
        foreach ($defaults as $i => [$nome,$slug,$cor,$finalizado]) {
            DB::table('statuses')->insert(['nome' => $nome, 'slug' => $slug, 'cor' => $cor, 'posicao' => $i, 'finalizado' => $finalizado, 'created_at' => now(), 'updated_at' => now()]);
        }
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->restrictOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('statuses')->restrictOnDelete();
            $table->index('nome');
            $table->index('data_entrega');
        });
        DB::table('ordens_servico')->orderBy('id')->each(function ($os) {
            $documento = preg_replace('/\D/', '', $os->documento ?? '') ?: null;
            $query = DB::table('clientes');
            $documento ? $query->where('documento', $documento) : $query->where('nome', $os->nome ?? 'Cliente sem nome')->where('telefone', $os->telefone ?? '');
            $cliente = $query->first();
            $clienteId = $cliente?->id ?? DB::table('clientes')->insertGetId(['nome' => $os->nome ?? 'Cliente sem nome', 'documento' => $documento, 'telefone' => $os->telefone ?? '', 'endereco' => $os->endereco, 'cidade' => $os->cidade, 'bairro' => $os->bairro, 'uf' => $os->uf, 'cep' => $os->cep, 'created_at' => now(), 'updated_at' => now()]);
            $status = DB::table('statuses')->where('slug', $os->situacao)->value('id') ?? DB::table('statuses')->where('slug', 'orcamento')->value('id');
            DB::table('ordens_servico')->where('id', $os->id)->update(['cliente_id' => $clienteId, 'status_id' => $status]);
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['status_id']);
            $table->dropColumn(['cliente_id', 'status_id']);
            $table->dropIndex(['nome']);
            $table->dropIndex(['data_entrega']);
        });
        Schema::dropIfExists('statuses');
        Schema::dropIfExists('clientes');
    }
};
