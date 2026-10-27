<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
use App\Services\GerarDocumentoOrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class GestaoTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    private function novaOrdem(?Cliente $cliente = null): OrdemServico
    {
        $cliente ??= Cliente::factory()->create();
        $this->post(route('ordens.store'), ['cliente_id' => $cliente->id, 'status_id' => Status::where('slug', 'orcamento')->value('id'), 'descricao' => 'Banner da loja', 'quantidade' => 2, 'altura' => 1.5, 'largura' => 2, 'valor_produto_m2' => 20])->assertSessionHasNoErrors();

        return OrdemServico::latest('id')->firstOrFail();
    }

    public function test_paginas_e_mutacoes_exigem_login(): void
    {
        $cliente = Cliente::factory()->create();
        foreach (['/', '/ordens', '/ordens/create', '/clientes', '/clientes/create', '/clientes/'.$cliente->id, '/clientes/'.$cliente->id.'/dados', '/statuses', '/statuses/create', '/minha-conta'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach (['/ordens', '/clientes', '/statuses'] as $url) {
            $this->post($url, [])->assertRedirect(route('login'));
        }
        $this->delete('/clientes/'.$cliente->id)->assertRedirect(route('login'));
        foreach (['/ordens/1/edit', '/ordens/00000000-0000-4000-8000-000000000000/sucesso', '/clientes/'.$cliente->id.'/edit', '/statuses/1/edit'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach (['/ordens/1', '/clientes/'.$cliente->id, '/statuses/1', '/minha-conta/senha'] as $url) {
            $this->put($url, [])->assertRedirect(route('login'));
        }
        foreach (['/ordens/1', '/statuses/1'] as $url) {
            $this->delete($url)->assertRedirect(route('login'));
        }
        $this->assertDatabaseHas('clientes', ['id' => $cliente->id]);
    }

    public function test_login_logout_e_bloqueio_de_tentativas(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test', 'password' => Hash::make('SenhaSegura12345')]);
        $this->get('/login')->assertOk()->assertSee('Vamos ao trabalho?');
        $this->post('/login', ['email' => $user->email, 'password' => 'SenhaSegura12345'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get('/')->assertOk()->assertSee('Mapa da produção');
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'Errada'])->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'SenhaSegura12345'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_crud_de_cliente_validacao_e_escape(): void
    {
        $this->entrar();
        $this->post('/clientes', ['nome' => 'Maria <script>alert(1)</script>', 'telefone' => '(11) 99999-1234', 'documento' => '123.456.789-01', 'uf' => 'sp'])->assertSessionHasNoErrors();
        $cliente = Cliente::firstOrFail();
        $this->assertSame('12345678901', $cliente->documento);
        $this->assertSame('SP', $cliente->uf);
        $this->get(route('clientes.show', $cliente))->assertOk()->assertSee('Maria &lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('clientes.edit', $cliente))->assertOk();
        $this->put(route('clientes.update', $cliente), ['nome' => 'Maria Atualizada', 'telefone' => '11988887777', 'documento' => '12345678901'])->assertSessionHasNoErrors();
        $this->get(route('clientes.dados', $cliente))->assertOk()->assertJsonPath('nome', 'Maria Atualizada');
        $this->post('/clientes', ['nome' => 'Duplicado', 'telefone' => '11988887777', 'documento' => '12345678901'])->assertSessionHasErrors('documento');
        $this->post('/clientes', ['nome' => 'Invalido', 'telefone' => '123'])->assertSessionHasErrors('telefone');
        $this->get('/clientes?q=Maria')->assertOk()->assertSee('Maria Atualizada');
        $this->delete(route('clientes.destroy', $cliente))->assertRedirect(route('clientes.index'));
        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }

    public function test_status_crud_e_protecao_de_vinculos(): void
    {
        $this->entrar();
        Storage::fake('public');
        $this->post('/statuses', ['nome' => 'Aguardando material', 'cor' => '#2563eb', 'posicao' => 3, 'finalizado' => 0])->assertSessionHasNoErrors();
        $status = Status::where('nome', 'Aguardando material')->firstOrFail();
        $this->get(route('statuses.edit', $status))->assertOk();
        $this->put(route('statuses.update', $status), ['nome' => 'Material recebido', 'cor' => '#15803d', 'posicao' => 5, 'finalizado' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($status->fresh()->finalizado);
        $this->post('/statuses', ['nome' => 'Invalido', 'cor' => 'red;bad', 'posicao' => 0])->assertSessionHasErrors('cor');
        $this->delete(route('statuses.destroy', $status))->assertSessionHasNoErrors();
        $os = $this->novaOrdem();
        $this->delete(route('statuses.destroy', $os->status_id))->assertSessionHasErrors('status');
        $this->delete(route('clientes.destroy', $os->cliente_id))->assertSessionHasErrors('cliente');
        $this->assertDatabaseHas('ordens_servico', ['id' => $os->id]);
    }

    public function test_pesquisa_filtros_e_preservacao_do_historico(): void
    {
        $this->entrar();
        Storage::fake('public');
        $cliente = Cliente::factory()->create(['nome' => 'Cliente Original']);
        $os = $this->novaOrdem($cliente);
        $cliente->update(['nome' => 'Cliente Renomeado']);
        $this->get('/ordens?q=Original')->assertOk()->assertSee('Cliente Original');
        $this->get('/ordens?q=Renomeado')->assertOk()->assertSee('Cliente Original');
        $this->get('/ordens?status_id='.$os->status_id)->assertOk()->assertSee('Cliente Original');
        $concluido = Status::where('slug', 'concluida')->firstOrFail();
        $this->get('/ordens?status_id='.$concluido->id)->assertOk()->assertDontSee('Cliente Original')->assertSee('Nenhuma ordem');
        $this->get('/ordens?q=%27%20OR%201=1--')->assertOk()->assertSee('Nenhuma ordem');
        $this->assertSame('Cliente Original', $os->fresh()->nome);
    }

    public function test_edicao_recalcula_e_substitui_documento_com_seguranca(): void
    {
        $this->entrar();
        Storage::fake('public');
        $os = $this->novaOrdem();
        $anterior = $os->arquivo_path;
        $os->update(['data_aprovacao' => '2026-10-26', 'data_entrega' => '2026-11-10', 'observacoes' => 'Preservar observações', 'servico_realizado' => 'Corte e acabamento']);
        $this->get(route('ordens.edit', $os))->assertOk()->assertSee('Editar OS')
            ->assertSee('value="20.00"', false)->assertSee('value="2026-11-10"', false)
            ->assertSee('Preservar observações')->assertSee('Corte e acabamento');
        $this->put(route('ordens.update', $os), ['cliente_id' => $os->cliente_id, 'status_id' => Status::where('slug', 'concluida')->value('id'), 'descricao' => 'Banner alterado', 'quantidade' => 3, 'altura' => 2, 'largura' => 2, 'valor_produto_m2' => 10, 'valor_total_final' => 1, 'token' => 'alterado'])->assertSessionHasNoErrors();
        $os->refresh();
        $this->assertSame('120.00', $os->valor_total_final);
        $this->assertSame('Concluída', $os->situacao);
        $this->assertNotSame('alterado', $os->token);
        $this->assertNotSame($anterior, $os->arquivo_path);
        Storage::disk('public')->assertMissing($anterior);
        Storage::disk('public')->assertExists($os->arquivo_path);
        $this->mock(GerarDocumentoOrdemServico::class)->shouldReceive('gerar')->once()->andThrow(new \RuntimeException('Falha'));
        $this->put(route('ordens.update', $os), ['cliente_id' => $os->cliente_id, 'status_id' => $os->status_id, 'descricao' => 'Não salvar', 'quantidade' => 1])->assertSessionHasErrors('documento');
        $this->assertSame('Banner alterado', $os->fresh()->descricao);
        Storage::disk('public')->assertExists($os->arquivo_path);
        $this->delete(route('ordens.destroy', $os))->assertRedirect(route('ordens.index'));
        Storage::disk('public')->assertMissing($os->arquivo_path);
        $this->assertDatabaseMissing('ordens_servico', ['id' => $os->id]);
    }

    public function test_troca_de_senha_exige_senha_atual(): void
    {
        $user = $this->entrar();
        $this->get('/minha-conta')->assertOk();
        $this->put('/minha-conta/senha', ['current_password' => 'errada', 'password' => 'SenhaNova123456', 'password_confirmation' => 'SenhaNova123456'])->assertSessionHasErrors('current_password');
        $this->put('/minha-conta/senha', ['current_password' => 'password', 'password' => 'SenhaNova123456', 'password_confirmation' => 'SenhaNova123456'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('SenhaNova123456', $user->fresh()->password));
    }

    public function test_bootstrap_nao_redefine_senha_existente(): void
    {
        config(['grafica.admin_name' => 'Administrador', 'grafica.admin_email' => 'admin@goldpress.local', 'grafica.admin_password' => 'InicialSenha12345']);
        $this->artisan('app:bootstrap-admin')->assertSuccessful();
        $user = User::firstOrFail();
        $this->assertTrue(Hash::check('InicialSenha12345', $user->password));
        config(['grafica.admin_password' => 'OutraSenha12345']);
        $this->artisan('app:bootstrap-admin')->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('InicialSenha12345', $user->fresh()->password));
    }

    public function test_migracao_vincula_ordens_existentes_sem_perder_dados(): void
    {
        $migration = require database_path('migrations/2026_10_26_234328_create_clientes_and_statuses_tables.php');
        $migration->down();
        DB::table('ordens_servico')->insert([
            'nome' => 'Cliente legado', 'documento' => '123.456.789-01',
            'telefone' => '11999991234', 'descricao' => 'Pedido antigo',
            'situacao' => 'producao', 'token' => (string) Str::uuid(),
            'arquivo_path' => 'ordens/documento-original.docx', 'valor_total_final' => 150,
        ]);
        $migration->up();
        $os = OrdemServico::firstOrFail();
        $this->assertSame('Cliente legado', $os->cliente->nome);
        $this->assertSame('12345678901', $os->cliente->documento);
        $this->assertSame('producao', $os->status->slug);
        $this->assertSame('ordens/documento-original.docx', $os->arquivo_path);
        $this->assertSame('150.00', $os->valor_total_final);
    }

    public function test_paginacao_preserva_a_pesquisa_e_cadastro_invalido_nao_cria_registro(): void
    {
        $this->entrar();
        Cliente::factory()->count(16)->create(['nome' => 'Cliente paginação']);
        $this->get('/clientes?q=pagina')->assertOk()->assertSee('page=2', false);
        $this->get('/clientes?q=pagina&page=2')->assertOk()->assertSee('16–16 de 16 registros');
        $this->post('/ordens', ['cliente_id' => ['id' => 1], 'status_id' => 1, 'descricao' => 'Inválido', 'quantidade' => 1])->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('ordens_servico', 0);
    }
}
