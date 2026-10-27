<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordem_servico_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ordem_servico_id')->constrained('ordens_servico')->cascadeOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('tipo', 20);
            $table->string('descricao');
            $table->string('unidade', 20)->nullable();
            $table->string('codigo')->nullable();
            $table->string('referencia')->nullable();
            $table->string('formato')->nullable();
            $table->string('vias')->nullable();
            $table->string('cores')->nullable();
            $table->decimal('quantidade', 15, 4)->default(1);
            $table->decimal('largura', 15, 4)->nullable();
            $table->decimal('altura', 15, 4)->nullable();
            $table->decimal('area', 15, 4)->nullable();
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('valor_m2', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->unsignedInteger('posicao')->default(0);
            $table->timestamps();
            $table->index(['ordem_servico_id', 'posicao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordem_servico_itens');
    }
};
