<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\OrdemServico;
use App\Models\Perfil;
use App\Models\Status;
use App\Models\User;
use App\Services\GerarDocumentoOrdemServico;
use App\Services\MensagensWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MelhoriasOperacionaisTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(bool $admin = true): User
    {
        $user = $admin ? User::factory()->admin()->create() : User::factory()->operador()->create();
        $this->actingAs($user);

        return $user;
    }

    private function dados(): array
    {
        return [
            'cliente_id' => Cliente::factory()->create(['nome' => 'Cliente de teste'])->id,
            'status_id' => Status::where('slug', 'orcamento')->value('id'),
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Cartão de visita', 'quantidade' => 10, 'valor_unitario' => 10, 'unidade' => 'un']],
        ];
    }

    private function criar(array $extras = []): OrdemServico
    {
        Storage::fake('public');
        $this->post(route('ordens.store'), [...$this->dados(), ...$extras])->assertSessionHasNoErrors();

        return OrdemServico::latest('id')->firstOrFail();
    }

    public function test_cria_os_com_sinal_e_abre_preparacao_do_whatsapp(): void
    {
        $user = $this->entrar(false);
        $ordem = $this->criar(['sinal' => '30,00', 'acao' => 'salvar_whatsapp']);

        $this->assertSame(30.0, $ordem->valorPago());
        $this->assertSame(70.0, $ordem->saldoRestante());
        $this->assertDatabaseHas('pagamentos', ['ordem_servico_id' => $ordem->id, 'user_id' => $user->id, 'valor' => 30]);
        $this->get(route('ordens.success', ['token' => $ordem->token, 'whatsapp' => 1]))->assertOk()
            ->assertSee('data-os-acao-auto', false);
        $this->get(route('ordens.acao', [$ordem, 'whatsapp']))->assertOk()->assertSee('Pronto para envio')->assertSee('Mensagem pronta para envio');
    }

    public function test_rejeita_sinal_maior_que_total_sem_criar_os_ou_pagamento(): void
    {
        $this->entrar();
        $this->post(route('ordens.store'), [...$this->dados(), 'sinal' => '100,01'])->assertSessionHasErrors(['sinal' => 'O total da OS não pode ser menor que o valor já recebido e o sinal informado.']);

        $this->assertDatabaseCount('ordens_servico', 0);
        $this->assertDatabaseCount('pagamentos', 0);
    }

    public function test_pagamento_atualiza_saldo_e_reenvio_nao_duplica_recebimento(): void
    {
        $this->entrar(false);
        $ordem = $this->criar(['sinal' => 30]);
        $dados = ['chave' => (string) Str::uuid(), 'valor' => '70,00', 'data_pagamento' => today()->toDateString(), 'forma' => 'pix'];

        $this->post(route('ordens.pagamentos.store', $ordem), $dados)->assertSessionHasNoErrors();
        $this->post(route('ordens.pagamentos.store', $ordem), $dados)->assertSessionHasNoErrors();

        $this->assertSame(0.0, $ordem->fresh()->saldoRestante());
        $this->assertSame(100.0, $ordem->fresh()->valorPago());
        $this->assertDatabaseCount('pagamentos', 2);
        $this->get(route('ordens.acao', [$ordem, 'pagamentos']))->assertOk()->assertSee('OS quitada');
    }

    public function test_rejeita_pagamento_excedente_e_sinal_em_edicao(): void
    {
        $this->entrar();
        $ordem = $this->criar(['sinal' => 30]);
        $this->post(route('ordens.pagamentos.store', $ordem), ['chave' => (string) Str::uuid(), 'valor' => 71, 'data_pagamento' => today()->toDateString(), 'forma' => 'pix'])->assertSessionHasErrors('valor');
        $this->put(route('ordens.update', $ordem), [...$this->dados(), 'sinal' => 1])->assertSessionHasErrors('sinal');
        $this->assertSame(70.0, $ordem->fresh()->saldoRestante());
        $this->assertDatabaseCount('pagamentos', 1);
    }

    public function test_nao_reduz_os_abaixo_do_valor_recebido_e_permite_edicao_valida(): void
    {
        $this->entrar();
        $ordem = $this->criar(['sinal' => 80]);
        $dados = $this->dados();
        $this->put(route('ordens.update', $ordem), [...$dados, 'desconto' => 21])->assertSessionHasErrors('sinal');
        $this->assertSame('100.00', $ordem->fresh()->valor_total_final);
        $this->put(route('ordens.update', $ordem), [...$dados, 'desconto' => 20])->assertSessionHasNoErrors();
        $this->assertSame('80.00', $ordem->fresh()->valor_total_final);
        $this->assertSame(0.0, $ordem->fresh()->saldoRestante());
    }

    public function test_estorno_exige_admin_e_preserva_historico(): void
    {
        $this->entrar(false);
        $ordem = $this->criar(['sinal' => 30]);
        $pagamento = $ordem->pagamentos()->firstOrFail();
        $rota = route('ordens.pagamentos.estornar', [$ordem, $pagamento]);
        $this->patch($rota, ['motivo_estorno' => 'Lançamento duplicado'])->assertForbidden();
        $this->assertNull($pagamento->fresh()->estornado_em);

        $this->entrar();
        $this->patch($rota, ['motivo_estorno' => 'Lançamento duplicado'])->assertSessionHasNoErrors();
        $this->assertNotNull($pagamento->fresh()->estornado_em);
        $this->assertDatabaseHas('pagamentos', ['id' => $pagamento->id, 'motivo_estorno' => 'Lançamento duplicado']);
        $this->assertSame(100.0, $ordem->fresh()->saldoRestante());
    }

    public function test_filtros_de_entrega_excluem_finalizadas_e_painel_tem_atalhos(): void
    {
        $this->entrar();
        $this->travelTo(now()->setDate(2026, 10, 27)->startOfDay());
        $aberto = Status::where('slug', 'producao')->firstOrFail();
        $fechado = Status::where('slug', 'concluida')->firstOrFail();
        foreach ([['Pedido hoje', today(), $aberto], ['Pedido atrasado', today()->subDay(), $aberto], ['Pedido próximo', today()->addDays(2), $aberto], ['Pedido finalizado', today(), $fechado]] as [$nome, $data, $status]) {
            OrdemServico::factory()->create(['nome' => $nome, 'data_entrega' => $data, 'status_id' => $status->id]);
        }

        $this->get(route('ordens.index', ['prazo' => 'hoje']))->assertOk()->assertSee('Pedido hoje')->assertDontSee('Pedido atrasado')->assertDontSee('Pedido finalizado');
        $this->get(route('ordens.index', ['prazo' => 'proximas']))->assertOk()->assertSee('Pedido próximo')->assertDontSee('Pedido hoje');
        $this->get(route('ordens.index', ['prazo' => 'atrasadas']))->assertOk()->assertSee('Pedido atrasado')->assertDontSee('Pedido hoje');
        $this->get('/')->assertOk()->assertSee('Prioridades de entrega')->assertViewHas('hoje', 1)->assertViewHas('proximas', 1)->assertViewHas('atrasadas', 1);
    }

    public function test_filtra_saldo_a_receber_e_quitadas(): void
    {
        $this->entrar();
        $pendente = $this->criar(['sinal' => 30]);
        $quitada = $this->criar(['sinal' => 100]);
        $this->get(route('ordens.index', ['pagamento' => 'pendente']))->assertOk()->assertViewHas('ordens', fn ($ordens) => $ordens->modelKeys() === [$pendente->id]);
        $this->get(route('ordens.index', ['pagamento' => 'quitado']))->assertOk()->assertViewHas('ordens', fn ($ordens) => $ordens->modelKeys() === [$quitada->id]);
    }

    public function test_operador_altera_status_sem_mudar_itens_ou_pagamentos(): void
    {
        $this->entrar(false);
        $ordem = $this->criar(['sinal' => 30]);
        $arquivo = $ordem->arquivo_path;
        $status = Status::where('slug', 'producao')->firstOrFail();

        $this->patch(route('ordens.status', $ordem), ['status_id' => $status->id])->assertSessionHasNoErrors();

        $this->assertSame($status->id, $ordem->fresh()->status_id);
        $this->assertSame('Em produção', $ordem->fresh()->situacao);
        $this->assertCount(1, $ordem->fresh()->itens);
        $this->assertSame(30.0, $ordem->fresh()->valorPago());
        Storage::disk('public')->assertMissing($arquivo);
        Storage::disk('public')->assertExists($ordem->fresh()->arquivo_path);
    }

    public function test_falha_ao_gerar_documento_preserva_status_e_arquivo_anterior(): void
    {
        $this->entrar();
        $ordem = $this->criar();
        $arquivo = $ordem->arquivo_path;
        $this->mock(GerarDocumentoOrdemServico::class)->shouldReceive('gerar')->once()->andThrow(new \RuntimeException('Falha'));

        $this->patch(route('ordens.status', $ordem), ['status_id' => Status::where('slug', 'producao')->value('id')])->assertSessionHasErrors('status_id');

        $this->assertSame($ordem->status_id, $ordem->fresh()->status_id);
        $this->assertSame($arquivo, $ordem->fresh()->arquivo_path);
        Storage::disk('public')->assertExists($arquivo);
    }

    public function test_repetir_preenche_os_sem_criar_registro_ou_copiar_pagamentos_e_datas(): void
    {
        $this->entrar();
        $ordem = $this->criar(['sinal' => 30, 'data_entrega' => '2026-12-01']);
        $this->get(route('ordens.create', ['repetir' => $ordem->id]))->assertOk()->assertSee('Repetindo a OS')->assertSee('Cartão de visita')
            ->assertViewHas('ordem', null)->assertViewHas('origem', fn ($origem) => $origem->data_entrega === null && $origem->data_aprovacao === null);
        $this->assertDatabaseCount('ordens_servico', 1);
        $this->assertDatabaseCount('pagamentos', 1);
        $this->assertSame('2026-12-01', $ordem->fresh()->data_entrega->toDateString());
    }

    public function test_cadastro_rapido_retorna_cliente_e_valida_duplicidade(): void
    {
        $this->entrar(false);
        $dados = ['nome' => 'Maria <script>alert(1)</script>', 'telefone' => '(11) 99999-1234', 'documento' => '12345678901', 'perfil_id' => 1];
        $this->postJson(route('clientes.store'), $dados)->assertCreated()->assertJsonPath('nome', $dados['nome'])->assertJsonPath('telefone', '11999991234')->assertJsonMissingPath('perfil_id');
        $this->postJson(route('clientes.store'), $dados)->assertUnprocessable()->assertJsonValidationErrors('documento');
        $this->assertDatabaseCount('clientes', 1);
    }

    public function test_links_fixos_entregam_pdf_e_docx_atualizados_sem_login(): void
    {
        $this->entrar();
        $ordem = $this->criar();
        $link = route('documentos.show', ['token' => $ordem->token, 'formato' => 'docx']);
        $this->put(route('ordens.update', $ordem), [...$this->dados(), 'observacoes' => 'Documento atualizado'])->assertSessionHasNoErrors();
        auth()->logout();

        $this->get($link)->assertOk()->assertDownload('OS-'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT).'.docx');
        $pdf = $this->get(route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());
        $this->get(route('ordens.success', $ordem->token))->assertRedirect(route('login'));
        $this->get('/documentos/'.Str::uuid().'/pdf')->assertNotFound();
        $this->get('/documentos/'.$ordem->token.'/html')->assertNotFound();
    }

    public function test_mensagens_tem_empresa_itens_valores_prazo_e_link_fixo(): void
    {
        $this->entrar();
        $ordem = $this->criar(['sinal' => 30, 'data_entrega' => '2026-12-01']);
        $modelos = app(MensagensWhatsApp::class)->modelos($ordem);
        $this->assertStringContainsString('Gold Press', $modelos['pedido']);
        $this->assertStringContainsString('Cartão de visita', $modelos['pedido']);
        $this->assertStringContainsString('R$ 70,00', $modelos['pedido']);
        $this->assertStringContainsString('01/12/2026', $modelos['pedido']);
        $this->assertStringContainsString(route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']), $modelos['envio']);
        $this->assertStringContainsString('pronto para envio', $modelos['envio']);
        $this->assertStringContainsString('confirmar o endereço', $modelos['envio']);
        $this->assertStringContainsString('pronto para retirada', $modelos['retirada']);
    }

    public function test_permissoes_restringem_administracao_e_exclusao_para_operador(): void
    {
        $operador = $this->entrar(false);
        $admin = User::factory()->admin()->create();
        $this->assertFalse(Gate::forUser($operador)->allows('administrar'));
        $this->assertTrue(Gate::forUser($admin)->allows('administrar'));
        foreach (['/usuarios', '/usuarios/create', '/empresa', '/statuses', '/produtos'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $ordem = $this->criar();
        $this->delete(route('ordens.destroy', $ordem))->assertForbidden();
        $this->put(route('usuarios.update', $operador), ['name' => 'Operador', 'email' => $operador->email, 'perfil_id' => $admin->perfil_id])->assertForbidden();
        $this->assertSame(Perfil::OPERADOR, $operador->fresh()->perfil->slug);
        $this->assertDatabaseHas('ordens_servico', ['id' => $ordem->id]);
    }

    public function test_nao_rebaixa_o_ultimo_administrador(): void
    {
        $admin = $this->entrar();
        $operador = User::factory()->operador()->create();
        $this->put(route('usuarios.update', $admin), ['name' => $admin->name, 'email' => $admin->email, 'perfil_id' => $operador->perfil_id])->assertSessionHasErrors('perfil_id');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_novas_mutacoes_exigem_autenticacao(): void
    {
        $ordem = OrdemServico::factory()->create();
        $this->patch(route('ordens.status', $ordem), [])->assertRedirect(route('login'));
        $this->post(route('ordens.pagamentos.store', $ordem), [])->assertRedirect(route('login'));
    }

    public function test_valida_acoes_antes_de_criar_os(): void
    {
        $this->entrar();
        $dados = $this->dados();
        $this->post(route('ordens.store'), [...$dados, 'acao' => 'desconhecida'])->assertSessionHasErrors('acao');
        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_acao_pdf_e_whatsapp_redirecionam_para_etapa_escolhida(): void
    {
        $this->entrar();
        Storage::fake('public');
        foreach (['salvar_pdf' => ['baixar' => 'pdf'], 'salvar_whatsapp' => ['whatsapp' => 1]] as $acao => $parametros) {
            $resposta = $this->post(route('ordens.store'), [...$this->dados(), 'acao' => $acao]);
            $ordem = OrdemServico::latest('id')->firstOrFail();
            $resposta->assertRedirect(route('ordens.success', ['token' => $ordem->token, ...$parametros]));
        }
    }

    public function test_pdf_escapa_textos_do_cliente_e_da_os(): void
    {
        $this->entrar();
        $ordem = $this->criar(['observacoes' => '<script>alert(1)</script>']);
        $ordem->nome = '<img src="https://exemplo.test/foto.png">';
        $html = view('ordens.pdf', ['ordem' => $ordem, 'empresa' => Empresa::atual()])->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<img src=', $html);
    }
}
