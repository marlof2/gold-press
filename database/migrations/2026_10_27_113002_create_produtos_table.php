<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('tipo', 20)->default('unitario');
            $table->string('unidade', 20)->nullable();
            $table->decimal('valor_unitario', 15, 2)->default(0);
            $table->decimal('valor_m2', 15, 2)->default(0);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->index(['tipo', 'ativo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
