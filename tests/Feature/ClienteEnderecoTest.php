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
