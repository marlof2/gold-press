<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_editar_cliente_por_json_retorna_campos_atuais_e_mantem_pagina_convencional(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create(['nome' => 'Cliente atual', 'documento' => '12345678901', 'numero_endereco' => '47A', 'observacoes' => 'Atender no período da tarde']);
        $this->getJson(route('clientes.edit', $cliente))->assertOk()->assertJsonPath('id', $cliente->id)
            ->assertJsonPath('nome', 'Cliente atual')->assertJsonPath('numero_endereco', '47A')->assertJsonPath('observacoes', 'Atender no período da tarde');
        $this->get(route('clientes.edit', $cliente))->assertOk()->assertSee('Editar cliente')->assertSee('Cliente atual');
    }

    public function test_salvar_edicao_por_json_atualiza_cliente_sem_duplicar_ou_alterar_historico_da_os(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create(['nome' => 'Cliente anterior', 'documento' => '12345678901']);
        $ordem = OrdemServico::factory()->create(['cliente_id' => $cliente->id, 'nome' => 'Cliente anterior', 'telefone' => '11999991234', 'endereco' => 'Rua de execução', 'numero_endereco' => '88']);
        $this->putJson(route('clientes.update', $cliente), [
            'nome' => 'Cliente atualizado', 'documento' => $cliente->documento, 'telefone' => '(11) 97777-6666',
            'email' => 'cliente@example.test', 'endereco' => 'Rua do cadastro', 'numero_endereco' => '47B', 'observacoes' => 'Nova observação',
        ])->assertOk()->assertJsonPath('id', $cliente->id)->assertJsonPath('nome', 'Cliente atualizado')
            ->assertJsonPath('telefone', '11977776666')->assertJsonPath('numero_endereco', '47B')->assertJsonPath('observacoes', 'Nova observação');
        $this->assertDatabaseCount('clientes', 1);
        $this->assertSame('Cliente atualizado', $cliente->fresh()->nome);
        $this->assertSame('Cliente anterior', $ordem->fresh()->nome);
        $this->assertSame('Rua de execução', $ordem->fresh()->endereco);
        $this->assertSame('88', $ordem->fresh()->numero_endereco);
    }

    public function test_erros_de_edicao_json_preservam_cadastro_existente(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create(['nome' => 'Cliente original', 'documento' => '12345678901']);
        Cliente::factory()->create(['documento' => '98765432109']);
        $this->putJson(route('clientes.update', $cliente), ['nome' => 'Cliente alterado', 'telefone' => '123', 'documento' => '98765432109'])
            ->assertUnprocessable()->assertJsonValidationErrors(['telefone', 'documento']);
        $this->assertSame('Cliente original', $cliente->fresh()->nome);
        $this->assertSame('12345678901', $cliente->fresh()->documento);
        $this->assertDatabaseCount('clientes', 2);
    }

    public function test_edicao_json_exige_login(): void
    {
        $cliente = Cliente::factory()->create();
        $this->getJson(route('clientes.edit', $cliente))->assertUnauthorized();
        $this->putJson(route('clientes.update', $cliente), [])->assertUnauthorized();
    }
}
