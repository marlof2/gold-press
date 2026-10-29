<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $agora = now();
        DB::table('permissoes')->insertOrIgnore(['nome' => 'Visualizar faturamento', 'slug' => 'faturamento.visualizar', 'grupo' => 'faturamento', 'created_at' => $agora, 'updated_at' => $agora]);
        $id = DB::table('permissoes')->where('slug', 'faturamento.visualizar')->value('id');
        foreach (DB::table('perfis')->pluck('id') as $perfilId) {
            DB::table('perfil_permissao')->insertOrIgnore(['perfil_id' => $perfilId, 'permissao_id' => $id, 'created_at' => $agora, 'updated_at' => $agora]);
        }
    }

    public function down(): void
    {
        DB::table('permissoes')->where('slug', 'faturamento.visualizar')->delete();
    }
};
