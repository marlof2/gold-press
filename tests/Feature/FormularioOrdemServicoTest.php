<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormularioOrdemServicoTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(): User
    {
        $usuario = User::factory()->admin()->create();
        $this->actingAs($usuario);

        return $usuario;
    }

    private function dados(): array
    {
        return [
            'cliente_id' => Cliente::factory()->create()->id,
            'status_id' => Status::where('slug', 'orcamento')->value('id'),
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Cartão de visita', 'quantidade' => 100, 'valor_unitario' => 1]],
        ];
    }

    public function test_nova_os_tem_identificacao_antes_do_cliente_com_combo_pesquisavel_e_sem_numero(): void
    {
        $usuario = $this->entrar();
        $this->get(route('ordens.create'))->assertOk()
            ->assertSeeInOrder(['Identificação da OS', 'id="status_id"', '<h2>Cliente</h2>', 'id="cliente_id"'], false)
            ->assertSee('data-searchable-select', false)
            ->assertDontSee('cliente-busca', false)
            ->assertDontSee('Número da OS')
            ->assertDontSee('os-numero', false)
            ->assertDontSee('os-data', false)
            ->assertSee('Responsável da gráfica')
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->contains('id', $usuario->id));
    }

    public function test_edicao_exibe_numero_e_data_da_os_separados_do_cliente(): void
    {
        $usuario = $this->entrar();
        $ordem = OrdemServico::factory()->create(['responsavel_id' => $usuario->id, 'responsavel' => $usuario->name]);
        $this->get(route('ordens.edit', $ordem))->assertOk()
            ->assertSeeInOrder(['Identificação da OS', 'Número da OS', '#'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT), 'Criada em', '<h2>Cliente</h2>'], false)
            ->assertSee('id="responsavel_id" name="responsavel_id"', false)
            ->assertSee($usuario->name)
            ->assertDontSee('id="os-numero"', false);
    }

    public function test_responsavel_selecionado_e_usuario_da_empresa_e_nome_e_derivado_no_servidor(): void
    {
        $this->entrar();
        $responsavel = User::factory()->operador()->create(['name' => 'Responsável da produção']);
        Storage::fake('public');

        $this->post(route('ordens.store'), [...$this->dados(), 'responsavel_id' => $responsavel->id, 'responsavel' => 'Nome enviado indevidamente'])->assertSessionHasNoErrors();

        $ordem = OrdemServico::firstOrFail();
        $this->assertSame($responsavel->id, $ordem->responsavel_id);
        $this->assertSame($responsavel->name, $ordem->responsavel);
        $this->assertSame($responsavel->id, $ordem->usuarioResponsavel->id);
    }

    public function test_rejeita_responsavel_que_nao_e_usuario_cadastrado(): void
    {
        $this->entrar();
        $this->post(route('ordens.store'), [...$this->dados(), 'responsavel_id' => 999999])->assertSessionHasErrors('responsavel_id');
        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_ordem_legada_mantem_nome_anterior_ate_selecionar_um_usuario(): void
    {
        $usuario = $this->entrar();
        Storage::fake('public');
        $ordem = OrdemServico::factory()->create(['responsavel' => 'Atendente anterior', 'responsavel_id' => null]);
        $this->get(route('ordens.edit', $ordem))->assertOk()->assertSee('Manter: Atendente anterior (registro anterior)');

        $dados = $this->dados();
        $this->put(route('ordens.update', $ordem), [...$dados, 'responsavel_id' => null])->assertSessionHasNoErrors();
        $this->assertSame('Atendente anterior', $ordem->fresh()->responsavel);
        $this->put(route('ordens.update', $ordem), [...$dados, 'responsavel_id' => $usuario->id])->assertSessionHasNoErrors();
        $this->assertSame($usuario->id, $ordem->fresh()->responsavel_id);
        $this->assertSame($usuario->name, $ordem->fresh()->responsavel);
    }

    public function test_excluir_usuario_preserva_nome_do_responsavel_na_os(): void
    {
        $this->entrar();
        $usuario = User::factory()->operador()->create(['name' => 'Atendente histórico']);
        $ordem = OrdemServico::factory()->create(['responsavel_id' => $usuario->id, 'responsavel' => $usuario->name]);

        $usuario->delete();

        $this->assertNull($ordem->fresh()->responsavel_id);
        $this->assertSame('Atendente histórico', $ordem->fresh()->responsavel);
    }

    public function test_pode_limpar_responsavel_atualmente_vinculado(): void
    {
        $usuario = $this->entrar();
        Storage::fake('public');
        $ordem = OrdemServico::factory()->create(['responsavel_id' => $usuario->id, 'responsavel' => $usuario->name]);

        $this->put(route('ordens.update', $ordem), [...$this->dados(), 'responsavel_id' => null])->assertSessionHasNoErrors();

        $this->assertNull($ordem->fresh()->responsavel_id);
        $this->assertNull($ordem->fresh()->responsavel);
    }
}
