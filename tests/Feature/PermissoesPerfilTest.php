<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Produto;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\PerfilSeeder;
use Database\Seeders\PermissaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissoesPerfilTest extends TestCase
{
    use RefreshDatabase;

    private function revogar(User $user, string $slug): void
    {
        $user->perfil->permissoes()->detach(Permissao::where('slug', $slug)->value('id'));
        $user->unsetRelation('perfil');
    }

    public function test_migracao_atribui_todas_as_permissoes_a_todos_os_perfis_existentes(): void
    {
        $admin = User::factory()->admin()->create();
        $operador = User::factory()->operador()->create();
        $outro = Perfil::create(['nome' => 'Atendimento', 'slug' => 'atendimento']);
        $migracao = require database_path('migrations/2026_10_29_134150_create_permissoes_and_perfil_permissao_tables.php');
        $migracao->down();
        $migracao->up();

        $this->assertDatabaseCount('permissoes', 31);
        foreach ([$admin->perfil_id, $operador->perfil_id, $outro->id] as $id) {
            $this->assertSame(31, Perfil::findOrFail($id)->permissoes()->count());
        }
        $this->actingAs($operador->fresh());
        $this->get(route('usuarios.index'))->assertOk();
        $this->get(route('perfis.index'))->assertOk();
    }

    public function test_seed_repetido_nao_restaura_permissao_revogada_nem_duplica(): void
    {
        $user = User::factory()->operador()->create();
        $this->revogar($user, 'produtos.excluir');
        $this->seed([PermissaoSeeder::class, PerfilSeeder::class]);
        $this->seed([PermissaoSeeder::class, PerfilSeeder::class]);
        $this->assertDatabaseCount('permissoes', 31);
        $this->assertFalse($user->fresh()->can('produtos.excluir'));
        $this->assertTrue($user->fresh()->can('produtos.visualizar'));
    }

    public static function rotasProtegidas(): array
    {
        return [
            ['GET', '/', 'dashboard.visualizar'],
            ['GET', '/clientes', 'clientes.visualizar'],
            ['POST', '/clientes', 'clientes.criar'],
            ['PUT', '/clientes/1', 'clientes.editar'],
            ['DELETE', '/clientes/1', 'clientes.excluir'],
            ['GET', '/clientes/1/historico', 'ordens.visualizar'],
            ['GET', '/produtos', 'produtos.visualizar'],
            ['POST', '/produtos', 'produtos.criar'],
            ['PUT', '/produtos/1', 'produtos.editar'],
            ['DELETE', '/produtos/1', 'produtos.excluir'],
            ['GET', '/usuarios', 'usuarios.visualizar'],
            ['POST', '/usuarios', 'usuarios.criar'],
            ['PUT', '/usuarios/1', 'usuarios.editar'],
            ['DELETE', '/usuarios/1', 'usuarios.excluir'],
            ['GET', '/statuses', 'statuses.visualizar'],
            ['POST', '/statuses', 'statuses.criar'],
            ['PUT', '/statuses/1', 'statuses.editar'],
            ['DELETE', '/statuses/1', 'statuses.excluir'],
            ['GET', '/ordens', 'ordens.visualizar'],
            ['POST', '/ordens', 'ordens.criar'],
            ['PUT', '/ordens/1', 'ordens.editar'],
            ['DELETE', '/ordens/1', 'ordens.excluir'],
            ['PATCH', '/ordens/1/status', 'ordens.status'],
            ['GET', '/ordens/1/acoes/status', 'ordens.status'],
            ['GET', '/ordens/1/acoes/pagamentos', 'pagamentos.visualizar'],
            ['POST', '/ordens/1/pagamentos', 'pagamentos.criar'],
            ['PATCH', '/ordens/1/pagamentos/1/estornar', 'pagamentos.estornar'],
            ['GET', '/empresa', 'empresa.visualizar'],
            ['PUT', '/empresa', 'empresa.editar'],
            ['GET', '/perfis', 'perfis.visualizar'],
            ['PUT', '/perfis/1', 'perfis.editar'],
        ];
    }

    #[DataProvider('rotasProtegidas')]
    public function test_permissao_revogada_bloqueia_requisicao_direta_inclusive_do_administrador(string $metodo, string $url, string $permissao): void
    {
        $user = User::factory()->admin()->create();
        $cliente = Cliente::factory()->create();
        Produto::factory()->create();
        $ordem = OrdemServico::factory()->create(['cliente_id' => $cliente->id]);
        $ordem->pagamentos()->create(['chave' => fake()->uuid(), 'valor' => 10, 'data_pagamento' => today(), 'forma' => 'pix', 'user_id' => $user->id]);
        $this->revogar($user, $permissao);
        $this->actingAs($user);
        $this->call($metodo, $url)->assertForbidden();
    }

    public function test_tela_de_permissoes_salva_alteracoes_que_valem_para_todos_os_usuarios_do_perfil(): void
    {
        $admin = User::factory()->admin()->create();
        $operador = User::factory()->operador()->create();
        $outro = User::factory()->operador()->create();
        $this->actingAs($admin);
        $perfil = $operador->perfil;
        $this->get(route('perfis.edit', $perfil))->assertOk()->assertSee('Registrar pagamentos e sinal');
        $ids = Permissao::whereIn('slug', ['clientes.visualizar', 'ordens.visualizar'])->pluck('id')->all();
        $this->put(route('perfis.update', $perfil), ['permissoes' => $ids])->assertRedirect(route('perfis.index'))->assertSessionHasNoErrors();
        $this->assertSame(2, $perfil->permissoes()->count());
        foreach ([$operador, $outro] as $user) {
            $this->assertTrue(Gate::forUser($user->fresh())->allows('clientes.visualizar'));
            $this->assertFalse(Gate::forUser($user->fresh())->allows('clientes.editar'));
        }
        $this->actingAs($operador->fresh());
        $this->get(route('clientes.index'))->assertOk()->assertDontSee('Novo cliente')->assertDontSee('Editar cliente');
        $this->get(route('usuarios.index'))->assertForbidden();
        $this->put(route('perfis.update', $perfil), ['permissoes' => Permissao::pluck('id')->all()])->assertForbidden();
    }

    public function test_nao_permite_remover_o_ultimo_acesso_a_gestao_de_permissoes(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);
        $this->put(route('perfis.update', $user->perfil), ['permissoes' => []])->assertSessionHasErrors('permissoes');
        $this->assertSame(31, $user->perfil->permissoes()->count());
    }

    public function test_valida_ids_e_exige_login_na_gestao_de_perfis(): void
    {
        $user = User::factory()->admin()->create();
        $this->get(route('perfis.index'))->assertRedirect(route('login'));
        $this->put(route('perfis.update', $user->perfil), ['permissoes' => []])->assertRedirect(route('login'));
        $this->actingAs($user);
        $this->putJson(route('perfis.update', $user->perfil), ['permissoes' => [999999]])->assertUnprocessable()->assertJsonValidationErrors('permissoes.0');
        $this->assertSame(31, $user->perfil->permissoes()->count());
    }

    public function test_nao_pode_trocar_perfil_do_usuario_sem_permissao_especifica(): void
    {
        $user = User::factory()->operador()->create();
        $admin = User::factory()->admin()->create();
        $this->revogar($user, 'usuarios.atribuir_perfil');
        $this->actingAs($user);
        $this->put(route('usuarios.update', $user), ['name' => $user->name, 'email' => $user->email, 'perfil_id' => $admin->perfil_id])->assertForbidden();
        $this->assertSame(Perfil::OPERADOR, $user->fresh()->perfil->slug);
    }

    public function test_sinal_exige_permissao_de_registrar_pagamentos(): void
    {
        $user = User::factory()->operador()->create();
        $this->revogar($user, 'pagamentos.criar');
        $this->actingAs($user);
        $cliente = Cliente::factory()->create();
        $this->post(route('ordens.store'), ['cliente_id' => $cliente->id, 'status_id' => Status::firstOrFail()->id, 'sinal' => 10,
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Impressão', 'quantidade' => 1, 'valor_unitario' => 20]],
        ])->assertForbidden();
        $this->assertDatabaseCount('pagamentos', 0);
    }
}
