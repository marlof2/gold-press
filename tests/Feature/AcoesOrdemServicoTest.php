<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
use App\Services\SalvarOrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AcoesOrdemServicoTest extends TestCase
{
    use RefreshDatabase;

    private function dados(): array
    {
        return [
            'cliente_id' => Cliente::factory()->create(['endereco' => 'Rua do cliente', 'cep' => '01001-000', 'bairro' => 'Centro', 'cidade' => 'São Paulo', 'uf' => 'SP'])->id,
            'status_id' => Status::where('slug', 'orcamento')->value('id'),
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Impressão', 'quantidade' => 1, 'valor_unitario' => 100]],
        ];
    }

    public function test_endereco_explicitamente_vazio_nao_e_reposto_pelo_cliente_ao_criar_ou_editar(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Storage::fake('public');
        $dados = $this->dados();
        $vazio = array_fill_keys(['cep', 'endereco', 'numero_endereco', 'bairro', 'cidade', 'uf'], '');
        $this->post(route('ordens.store'), [...$dados, ...$vazio])->assertSessionHasNoErrors();
        $ordem = OrdemServico::firstOrFail();
        foreach (array_keys($vazio) as $campo) {
            $this->assertNull($ordem->$campo);
        }
        $this->assertSame('Rua do cliente', Cliente::findOrFail($dados['cliente_id'])->endereco);

        $this->put(route('ordens.update', $ordem), [...$dados, 'endereco' => 'Endereço de execução'])->assertSessionHasNoErrors();
        $this->put(route('ordens.update', $ordem), [...$dados, ...$vazio])->assertSessionHasNoErrors();
        $this->assertNull($ordem->fresh()->endereco);
    }

    public function test_endereco_omitido_continua_usando_cadastro_do_cliente(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Storage::fake('public');
        $this->post(route('ordens.store'), $this->dados())->assertSessionHasNoErrors();
        $this->assertSame('Rua do cliente', OrdemServico::firstOrFail()->endereco);
    }

    public function test_listagem_e_detalhes_oferecem_acoes_sem_formularios_abertos(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $ordem = OrdemServico::factory()->create();
        $this->get(route('ordens.index'))->assertOk()->assertSee('data-os-acao', false)->assertSee('Mensagem / WhatsApp')->assertSee('Pagamentos e saldo')->assertDontSee('osf-inline-status');
        $this->get(route('ordens.success', $ordem->token))->assertOk()->assertSee('data-os-acao', false)
            ->assertDontSee('id="whatsapp-mensagem"', false)->assertDontSee('id="pagamento-valor"', false)->assertDontSee('id="status-ordem"', false);
    }

    public function test_operador_abre_modais_e_nao_recebe_layout_completo(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $ordem = OrdemServico::factory()->create();
        foreach (['status' => 'Novo status', 'whatsapp' => 'Mensagem pronta para envio', 'pagamentos' => 'Saldo restante'] as $acao => $texto) {
            $this->get(route('ordens.acao', [$ordem, $acao]))->assertOk()->assertSee($texto)->assertSee('data-os-acao-fechar', false)->assertDontSee('<html', false);
        }
        $this->get('/ordens/'.$ordem->id.'/acoes/desconhecida')->assertNotFound();
    }

    public function test_modais_exigem_login(): void
    {
        $ordem = OrdemServico::factory()->create();
        $this->get(route('ordens.acao', [$ordem, 'whatsapp']))->assertRedirect(route('login'));
    }

    public function test_status_e_pagamento_sao_salvos_por_json_e_erros_sao_retornados_para_modal(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        Storage::fake('public');
        $this->post(route('ordens.store'), $this->dados())->assertSessionHasNoErrors();
        $ordem = OrdemServico::firstOrFail();
        $status = Status::where('slug', 'producao')->firstOrFail();
        $this->patchJson(route('ordens.status', $ordem), ['status_id' => $status->id])->assertOk()->assertJsonPath('message', 'Status da OS atualizado.');
        $this->assertSame($status->id, $ordem->fresh()->status_id);
        $this->patchJson(route('ordens.status', $ordem), ['status_id' => 999999])->assertUnprocessable()->assertJsonValidationErrors('status_id');

        $dados = ['chave' => (string) Str::uuid(), 'valor' => '30,00', 'data_pagamento' => today()->toDateString(), 'forma' => 'pix'];
        $this->postJson(route('ordens.pagamentos.store', $ordem), $dados)->assertOk();
        $this->assertSame(70.0, $ordem->fresh()->saldoRestante());
        $this->postJson(route('ordens.pagamentos.store', $ordem), [...$dados, 'chave' => (string) Str::uuid(), 'valor' => 71])->assertUnprocessable()->assertJsonValidationErrors('valor');
        $this->assertDatabaseCount('pagamentos', 1);
    }

    public function test_falha_de_status_retorna_erro_json_e_preserva_os(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $ordem = OrdemServico::factory()->create();
        $this->mock(SalvarOrdemServico::class)->shouldReceive('alterarStatus')->once()->andThrow(new \RuntimeException('Falha ao gravar no banco'));
        $this->patchJson(route('ordens.status', $ordem), ['status_id' => Status::where('slug', 'producao')->value('id')])->assertStatus(500)->assertJsonPath('message', 'Não foi possível atualizar o status. Tente novamente.');
        $this->assertSame($ordem->status_id, $ordem->fresh()->status_id);
    }

    public function test_estorno_por_modal_exige_admin_e_retorna_confirmacao_json(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        Storage::fake('public');
        $this->post(route('ordens.store'), [...$this->dados(), 'sinal' => 30])->assertSessionHasNoErrors();
        $ordem = OrdemServico::firstOrFail();
        $pagamento = $ordem->pagamentos()->firstOrFail();
        $rota = route('ordens.pagamentos.estornar', [$ordem, $pagamento]);
        $this->patchJson($rota, ['motivo_estorno' => 'Lançamento duplicado'])->assertForbidden();
        $this->assertNull($pagamento->fresh()->estornado_em);

        $this->actingAs(User::factory()->admin()->create());
        $this->patchJson($rota, ['motivo_estorno' => 'Lançamento duplicado'])->assertOk()
            ->assertJsonPath('message', 'Pagamento estornado. O registro foi mantido no histórico.')
            ->assertSessionHas('success', 'Pagamento estornado. O registro foi mantido no histórico.');
        $this->assertNotNull($pagamento->fresh()->estornado_em);
        $this->assertSame(100.0, $ordem->fresh()->saldoRestante());
    }

    public function test_maps_usa_endereco_da_os_na_listagem_e_nos_detalhes(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create(['endereco' => 'Endereço diferente do cliente']);
        $ordem = OrdemServico::factory()->create([
            'cliente_id' => $cliente->id, 'endereco' => 'Rua A&B', 'numero_endereco' => '0',
            'bairro' => 'Centro', 'cidade' => 'São Paulo', 'uf' => 'SP', 'cep' => '01001-000',
        ]);
        $url = $ordem->urlMaps();
        parse_str(parse_url($url, PHP_URL_QUERY), $parametros);
        $this->assertStringStartsWith('https://www.google.com/maps/search/?', $url);
        $this->assertSame(['api' => '1', 'query' => 'Rua A&B, 0, Centro, São Paulo, SP, 01001-000, Brasil'], $parametros);
        $this->get(route('ordens.index'))->assertOk()->assertSee('Abrir no Maps')->assertSee($url)->assertSee('data-os-maps', false);
        $this->get(route('ordens.success', $ordem->token))->assertOk()->assertSee('Abrir no Maps')->assertSee($url)->assertDontSee($cliente->endereco);
    }

    public function test_os_sem_endereco_nao_exibe_acao_do_maps_mesmo_com_endereco_no_cliente(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create(['endereco' => 'Rua do cadastro']);
        $ordem = OrdemServico::factory()->create(['cliente_id' => $cliente->id, 'endereco' => null, 'numero_endereco' => null, 'bairro' => null, 'cidade' => null, 'uf' => null, 'cep' => null]);
        $this->assertNull($ordem->urlMaps());
        $this->get(route('ordens.index'))->assertOk()->assertDontSee('Abrir no Maps');
        $this->get(route('ordens.success', $ordem->token))->assertOk()->assertDontSee('Abrir no Maps');
    }
}
