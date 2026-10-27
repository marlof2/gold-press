<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->dropColumn(['arredondamento', 'acerto_valor', 'valor_servicos', 'deslocamento']);
        });
    }

    public function down(): void
    {
        Schema::table('ordens_servico', function (Blueprint $table) {
            $table->decimal('arredondamento', 15, 2)->default(0);
            $table->decimal('acerto_valor', 15, 2)->default(0);
            $table->decimal('valor_servicos', 15, 2)->default(0);
            $table->decimal('deslocamento', 15, 2)->default(0);
        });
    }
};
