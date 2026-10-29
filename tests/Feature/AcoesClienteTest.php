<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcoesClienteTest extends TestCase
{
    use RefreshDatabase;

    public function test_listagem_de_clientes_oferece_menu_de_acoes_e_atalho_para_ordens(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create();

        $this->get(route('clientes.index'))->assertOk()
            ->assertSee('popovertarget="acoes-clientes-'.$cliente->id.'"', false)
            ->assertSee('Ver cliente')->assertSee('Editar cliente')->assertSee('Histórico de ordens de serviço')
            ->assertSee(route('clientes.historico', $cliente), false)->assertSee('data-os-acao', false);
    }

    public function test_filtro_lista_somente_ordens_do_cliente_inclusive_com_nomes_iguais(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create(['nome' => 'Nome compartilhado']);
        $outro = Cliente::factory()->create(['nome' => 'Nome compartilhado']);
        $minha = OrdemServico::factory()->create(['cliente_id' => $cliente->id, 'nome' => 'Nome anterior', 'descricao' => 'Pedido deste cliente']);
        OrdemServico::factory()->create(['cliente_id' => $outro->id, 'descricao' => 'Pedido de outro cliente']);

        $this->get(route('ordens.index', ['cliente_id' => $cliente->id]))->assertOk()
            ->assertViewHas('ordens', fn ($ordens) => $ordens->total() === 1 && $ordens->first()->id === $minha->id)
            ->assertSee('Pedido deste cliente')->assertDontSee('Pedido de outro cliente')
            ->assertSee('Nome compartilhado')->assertSee('Ver todas as ordens')
            ->assertSee('name="cliente_id" value="'.$cliente->id.'"', false)
            ->assertSee(route('ordens.create', ['cliente_id' => $cliente->id]), false);
    }

    public function test_filtros_e_paginacao_mantem_cliente_selecionado(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create();
        $status = Status::where('slug', 'orcamento')->firstOrFail();
        OrdemServico::factory()->count(16)->create(['cliente_id' => $cliente->id, 'status_id' => $status->id, 'descricao' => 'Cartão de visita']);
        OrdemServico::factory()->create(['cliente_id' => $cliente->id, 'descricao' => 'Banner em lona']);
        $filtros = ['cliente_id' => $cliente->id, 'q' => 'Cartão', 'status_id' => $status->id];

        $this->get(route('ordens.index', $filtros))->assertOk()
            ->assertViewHas('ordens', fn ($ordens) => $ordens->total() === 16 && str_contains($ordens->nextPageUrl(), 'cliente_id='.$cliente->id))
            ->assertSee(route('ordens.index', ['cliente_id' => $cliente->id]), false);
        $this->get(route('ordens.index', [...$filtros, 'page' => 2]))->assertOk()
            ->assertViewHas('ordens', fn ($ordens) => $ordens->count() === 1 && $ordens->first()->cliente_id === $cliente->id);
    }

    public function test_cliente_sem_ordens_exibe_lista_vazia_e_cliente_invalido_e_rejeitado(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create();
        $this->get(route('ordens.index', ['cliente_id' => $cliente->id]))->assertOk()
            ->assertSee('Nenhuma ordem por aqui')->assertViewHas('ordens', fn ($ordens) => $ordens->total() === 0);
        $this->getJson(route('ordens.index', ['cliente_id' => 999999]))->assertUnprocessable()->assertJsonValidationErrors('cliente_id');
    }

    public function test_listagem_filtrada_exige_login(): void
    {
        $cliente = Cliente::factory()->create();
        $this->get(route('ordens.index', ['cliente_id' => $cliente->id]))->assertRedirect(route('login'));
        $this->get(route('clientes.historico', $cliente))->assertRedirect(route('login'));
    }

    public function test_historico_abre_como_conteudo_de_modal_com_acoes_e_paginacao_do_cliente(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create();
        $status = Status::where('slug', 'orcamento')->firstOrFail();
        $ordens = OrdemServico::factory()->count(11)->create(['cliente_id' => $cliente->id, 'status_id' => $status->id, 'descricao' => 'Pedido deste cliente']);
        OrdemServico::factory()->create(['descricao' => 'Pedido de outro cliente']);

        $this->get(route('clientes.show', $cliente))->assertOk()
            ->assertSee(route('clientes.historico', $cliente), false)->assertDontSee('Ordem / produto');
        $this->get(route('clientes.historico', $cliente))->assertOk()
            ->assertSee('data-cliente-historico', false)->assertSee('Histórico de ordens de serviço')
            ->assertSee('Pedido deste cliente')->assertDontSee('Pedido de outro cliente')
            ->assertSee('Alterar status')->assertSee('Baixar PDF')->assertSee('Editar ordem')
            ->assertSee('popovertarget="acoes-historico-cliente-', false)
            ->assertSee('rel="next"', false)->assertDontSee('<html', false)
            ->assertViewHas('ordens', fn ($pagina) => $pagina->total() === 11 && $pagina->count() === 10);
        $this->get(route('clientes.historico', [$cliente, 'page' => 2]))->assertOk()
            ->assertViewHas('ordens', fn ($pagina) => $pagina->count() === 1 && $pagina->first()->id === $ordens->first()->id);
    }

    public function test_modal_de_historico_mostra_estado_vazio_para_cliente_sem_os(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create();
        $this->get(route('clientes.historico', $cliente))->assertOk()
            ->assertSee('Nenhuma ordem por aqui')->assertSee(route('ordens.create', ['cliente_id' => $cliente->id]), false);
    }
}
