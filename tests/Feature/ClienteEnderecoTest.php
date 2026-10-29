<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClienteEnderecoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_pode_ser_cadastrado_sem_telefone_e_remover_telefone_na_edicao(): void
    {
        $this->actingAs(User::factory()->operador()->create());

        $this->post(route('clientes.store'), ['nome' => 'Cliente sem telefone'])->assertSessionHasNoErrors();
        $cliente = Cliente::firstOrFail();
        $this->assertNull($cliente->telefone);

        $this->postJson(route('clientes.store'), ['nome' => 'Cadastro rápido sem telefone', 'telefone' => ''])
            ->assertCreated()->assertJsonPath('telefone', null);

        $cliente->update(['telefone' => '11999991234']);
        $this->putJson(route('clientes.update', $cliente), ['nome' => $cliente->nome, 'telefone' => ''])
            ->assertOk()->assertJsonPath('telefone', null);
        $this->assertNull($cliente->fresh()->telefone);
    }

    public function test_cliente_sem_telefone_pode_criar_editar_e_visualizar_os(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create(['telefone' => null]);
        $dados = [
            'cliente_id' => $cliente->id,
            'status_id' => Status::where('slug', 'orcamento')->value('id'),
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Impressão', 'quantidade' => 1, 'valor_unitario' => 10]],
        ];

        $this->post(route('ordens.store'), $dados)->assertSessionHasNoErrors();
        $ordem = OrdemServico::firstOrFail();
        $this->assertNull($ordem->telefone);
        $this->put(route('ordens.update', $ordem), $dados)->assertSessionHasNoErrors();
        $this->assertNull($ordem->fresh()->telefone);
        $this->get(route('ordens.success', $ordem->token))->assertOk();
        $this->get(route('ordens.acao', [$ordem, 'whatsapp']))->assertOk()
            ->assertSee('Cliente sem telefone cadastrado.')
            ->assertDontSee('data-whatsapp-abrir', false);
        $this->get(route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_numero_e_cadastrado_editado_e_retornado_nos_dados_do_cliente(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $dados = ['nome' => 'Cliente com endereço', 'telefone' => '11999991234', 'endereco' => 'Rua do Cliente', 'numero_endereco' => '47A'];
        $this->get(route('clientes.create'))->assertOk()->assertSee('name="numero_endereco"', false);
        $this->post(route('clientes.store'), $dados)->assertSessionHasNoErrors();
        $cliente = Cliente::firstOrFail();
        $this->assertSame('47A', $cliente->numero_endereco);
        $this->get(route('clientes.edit', $cliente))->assertOk()->assertSee('value="47A"', false);
        $this->get(route('clientes.show', $cliente))->assertOk()->assertSee('Número')->assertSee('47A');
        $this->get(route('clientes.dados', $cliente))->assertOk()->assertJsonPath('numero_endereco', '47A');
        $this->get(route('ordens.create'))->assertOk()->assertSee('"numero_endereco":"47A"');

        $this->put(route('clientes.update', $cliente), [...$dados, 'numero_endereco' => '123'])->assertSessionHasNoErrors();
        $this->assertSame('123', $cliente->fresh()->numero_endereco);
    }

    public function test_cadastro_rapido_retorna_numero_e_valida_tamanho_sem_exigir_numero(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $dados = ['nome' => 'Cliente rápido', 'telefone' => '11999991234', 'numero_endereco' => 'S/N'];
        $this->postJson(route('clientes.store'), $dados)->assertCreated()->assertJsonPath('numero_endereco', 'S/N');
        $this->postJson(route('clientes.store'), [...$dados, 'numero_endereco' => str_repeat('1', 21)])->assertUnprocessable()->assertJsonValidationErrors('numero_endereco');
        $this->postJson(route('clientes.store'), [...$dados, 'numero_endereco' => null])->assertCreated()->assertJsonPath('numero_endereco', null);
        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_numero_do_cliente_preenche_os_sem_substituir_numero_informado_ou_alterar_historico(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        Storage::fake('public');
        $cliente = Cliente::factory()->create(['endereco' => 'Rua do Cliente', 'numero_endereco' => '47A']);
        $dados = [
            'cliente_id' => $cliente->id,
            'status_id' => Status::where('slug', 'orcamento')->value('id'),
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Impressão', 'quantidade' => 1, 'valor_unitario' => 10]],
        ];
        $this->post(route('ordens.store'), $dados)->assertSessionHasNoErrors();
        $original = OrdemServico::firstOrFail();
        $this->assertSame('47A', $original->numero_endereco);
        $cliente->update(['numero_endereco' => '99']);
        $this->assertSame('47A', $original->fresh()->numero_endereco);

        $this->post(route('ordens.store'), [...$dados, 'numero_endereco' => '88'])->assertSessionHasNoErrors();
        $outra = OrdemServico::latest('id')->firstOrFail();
        $this->assertSame('88', $outra->numero_endereco);
        $this->put(route('ordens.update', $outra), [...$dados, 'numero_endereco' => ''])->assertSessionHasNoErrors();
        $this->assertNull($outra->fresh()->numero_endereco);
    }
}
