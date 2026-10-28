<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use App\Models\OrdemServicoItem;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdutoTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(): User
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_crud_de_produtos(): void
    {
        $this->entrar();
        $this->get('/produtos')->assertOk()->assertSee('Produtos')->assertDontSee('<th>Unidade</th>', false);
        $this->get(route('produtos.create'))->assertOk()->assertDontSee('name="unidade"', false);
        $this->post('/produtos', ['nome' => 'Cartão de visita', 'tipo' => 'unitario', 'valor_unitario' => '1,50', 'ativo' => 1])->assertSessionHasNoErrors();
        $produto = Produto::where('nome', 'Cartão de visita')->firstOrFail();
        $this->assertSame('1.50', $produto->valor_unitario);
        $this->assertSame('un', $produto->tipo->unidade());

        $this->get(route('produtos.edit', $produto))->assertOk()->assertSee('Cartão de visita')->assertDontSee('name="unidade"', false);
        $this->put(route('produtos.update', $produto), ['nome' => 'Cartão premium', 'tipo' => 'm2', 'valor_m2' => '90,00', 'ativo' => 0])->assertSessionHasNoErrors();
        $this->assertSame('90.00', $produto->fresh()->valor_m2);
        $this->assertSame('m²', $produto->fresh()->tipo->unidade());
        $this->assertFalse($produto->fresh()->ativo);

        $this->delete(route('produtos.destroy', $produto))->assertRedirect(route('produtos.index'));
        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }

    public function test_nao_exclui_produto_usado_em_item_de_os(): void
    {
        $this->entrar();
        $produto = Produto::factory()->unitario()->create();
        $os = OrdemServico::factory()->create();
        OrdemServicoItem::factory()->create(['ordem_servico_id' => $os->id, 'produto_id' => $produto->id]);

        $this->delete(route('produtos.destroy', $produto))->assertSessionHasErrors('produto');
        $this->assertDatabaseHas('produtos', ['id' => $produto->id]);
    }

    public function test_ignora_campo_de_unidade_obsoleto_em_requisicoes_antigas(): void
    {
        $this->entrar();
        $dados = ['nome' => 'Produto legado', 'tipo' => 'unitario', 'valor_unitario' => 10, 'unidade' => 'm²'];

        $this->post(route('produtos.store'), $dados)->assertSessionHasNoErrors();

        $produto = Produto::where('nome', 'Produto legado')->firstOrFail();
        $this->assertArrayNotHasKey('unidade', $produto->getAttributes());
        $this->assertSame('un', $produto->tipo->unidade());

        $this->put(route('produtos.update', $produto), [...$dados, 'tipo' => 'm2', 'valor_m2' => 20, 'unidade' => 'un'])->assertSessionHasNoErrors();
        $this->assertSame('m²', $produto->fresh()->tipo->unidade());
        $this->assertSame('20.00', $produto->fresh()->valor_m2);
    }

    public function test_valida_valor_obrigatorio_por_tipo(): void
    {
        $this->entrar();
        $this->post('/produtos', ['nome' => 'Sem valor', 'tipo' => 'unitario'])->assertSessionHasErrors('valor_unitario');
        $this->post('/produtos', ['nome' => 'Sem m2', 'tipo' => 'm2'])->assertSessionHasErrors('valor_m2');
        $this->post('/produtos', ['nome' => 'Tipo inválido', 'tipo' => 'litro'])->assertSessionHasErrors('tipo');
    }
}
