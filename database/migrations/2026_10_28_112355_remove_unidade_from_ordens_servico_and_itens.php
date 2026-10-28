<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordem_servico_itens', function (Blueprint $table) {
            $table->dropColumn('unidade');
        });

        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn('unidade');
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->string('unidade')->nullable();
        });

        Schema::table('ordem_servico_itens', function (Blueprint $table) {
            $table->string('unidade', 20)->nullable();
        });
    }
};
