<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\OrdemServico;
use App\Models\Perfil;
use App\Models\Status;
use App\Models\User;
use App\Services\GerarDocumentoOrdemServico;
use App\Services\GerarPdfOrdemServico;
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
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Cartão de visita', 'quantidade' => 10, 'valor_unitario' => 10]],
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

    public function test_resumo_exibe_pago_e_saldo_e_modal_retorna_valores_atualizados(): void
    {
        $this->entrar();
        $ordem = $this->criar(['sinal' => 30]);
        $rota = route('ordens.success', $ordem->token);
        $this->get($rota)->assertOk()
            ->assertSeeInOrder(['Valor pago', 'R$ 30,00', 'Saldo restante', 'R$ 70,00'])
            ->assertDontSee('id="pagamento-valor"', false);
        $dados = ['chave' => (string) Str::uuid(), 'valor' => '20,00', 'data_pagamento' => today()->toDateString(), 'forma' => 'pix'];

        $this->postJson(route('ordens.pagamentos.store', $ordem), $dados)->assertOk()
            ->assertJsonPath('valor_pago', 50);

        $this->assertSame(50.0, $ordem->fresh()->saldoRestante());
        $this->get($rota)->assertOk()->assertSeeInOrder(['Valor pago', 'R$ 50,00', 'Saldo restante', 'R$ 50,00']);
        $this->get(route('ordens.acao', [$ordem, 'pagamentos']))->assertOk()
            ->assertSee('data-os-acao-sucesso', false)
            ->assertSeeInOrder(['Recebido', 'R$ 50,00', 'Saldo restante', 'R$ 50,00'])
            ->assertSee('Registrar pagamento');
        $pagamento = $ordem->pagamentos()->where('chave', $dados['chave'])->firstOrFail();

        $this->patchJson(route('ordens.pagamentos.estornar', [$ordem, $pagamento]), ['motivo_estorno' => 'Lançamento incorreto'])->assertOk()
            ->assertJsonPath('valor_pago', 30);

        $this->assertNotNull($pagamento->fresh()->estornado_em);
        $this->assertSame(70.0, $ordem->fresh()->saldoRestante());
        $this->get($rota)->assertOk()->assertSeeInOrder(['Valor pago', 'R$ 30,00', 'Saldo restante', 'R$ 70,00']);
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
        $status = Status::where('slug', 'producao')->firstOrFail();

        $this->patch(route('ordens.status', $ordem), ['status_id' => $status->id])->assertSessionHasNoErrors();

        $this->assertSame($status->id, $ordem->fresh()->status_id);
        $this->assertSame('Em produção', $ordem->fresh()->situacao);
        $this->assertCount(1, $ordem->fresh()->itens);
        $this->assertSame(30.0, $ordem->fresh()->valorPago());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_alterar_status_nao_depende_da_geracao_de_word(): void
    {
        $this->entrar();
        $ordem = $this->criar();
        $this->mock(GerarDocumentoOrdemServico::class)->shouldNotReceive('gerar');
        $status = Status::where('slug', 'producao')->firstOrFail();

        $this->patch(route('ordens.status', $ordem), ['status_id' => $status->id])->assertSessionHasNoErrors();

        $this->assertSame($status->id, $ordem->fresh()->status_id);
        $this->assertSame([], Storage::disk('public')->allFiles());
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

    public function test_pdf_e_gerado_a_cada_acesso_com_pagamentos_atuais_sem_salvar_arquivo(): void
    {
        $this->entrar();
        $ordem = $this->criar(['sinal' => 30]);
        $arquivos = Storage::disk('public')->allFiles();
        $recebidos = [];
        $gerador = new GerarPdfOrdemServico;
        $this->mock(GerarPdfOrdemServico::class)->shouldReceive('gerar')->twice()->andReturnUsing(function (OrdemServico $atual) use ($gerador, &$recebidos): string {
            $atual->loadMissing('pagamentos');
            $recebidos[] = $atual->valorPago();

            return $gerador->gerar($atual);
        });
        $rota = route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']);

        $primeiro = $this->get($rota)->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="OS-'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT).'.pdf"');
        $this->assertStringStartsWith('%PDF-', $primeiro->getContent());
        $this->postJson(route('ordens.pagamentos.store', $ordem), ['chave' => (string) Str::uuid(), 'valor' => 20, 'data_pagamento' => today()->toDateString(), 'forma' => 'pix'])->assertOk();
        $segundo = $this->get($rota.'?download=1')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Content-Disposition', 'attachment; filename="OS-'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT).'.pdf"');

        $this->assertStringStartsWith('%PDF-', $segundo->getContent());
        $this->assertSame([30.0, 50.0], $recebidos);
        $this->assertSame($arquivos, Storage::disk('public')->allFiles());
    }

    public function test_word_reflete_pagamentos_e_edicoes_sem_armazenar_arquivos(): void
    {
        $this->entrar();
        $ordem = $this->criar(['sinal' => 30]);
        $temporarios = glob(sys_get_temp_dir().'/ordem-*');
        $xml = $this->documentoXml($ordem);
        $this->assertStringContainsString('Valor pago', $xml);
        $this->assertStringContainsString('R$ 30,00', $xml);
        $this->assertStringContainsString('R$ 70,00', $xml);

        $this->postJson(route('ordens.pagamentos.store', $ordem), ['chave' => (string) Str::uuid(), 'valor' => 20, 'data_pagamento' => today()->toDateString(), 'forma' => 'pix'])->assertOk();
        $ordem->update(['observacoes' => 'Observação atualizada depois do primeiro download']);
        $atualizado = $this->documentoXml($ordem);

        $this->assertStringContainsString('Observação atualizada depois do primeiro download', $atualizado);
        $this->assertStringContainsString('R$ 50,00', $atualizado);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame($temporarios, glob(sys_get_temp_dir().'/ordem-*'));
    }

    public function test_pdf_e_word_usam_mesmo_logo_e_campos_dos_itens(): void
    {
        $this->entrar();
        $ordem = $this->criar();
        $ordem->itens()->firstOrFail()->update(['codigo' => 'CV-01', 'referencia' => 'Arte aprovada', 'formato' => '9 x 5 cm', 'cores' => '4x4', 'vias' => '1']);
        $ordem->refresh();
        $html = view('ordens.pdf', ['ordem' => $ordem, 'empresa' => Empresa::atual()])->render();
        $xml = $this->documentoXml($ordem);

        $this->assertStringContainsString('data:image/png;base64,'.base64_encode(file_get_contents(public_path('img/logo-gold-press.png'))), $html);
        foreach (['Produto / serviço', 'Qtd.', 'Medidas', 'Preço', 'Subtotal', 'Cód.: CV-01', 'Ref.: Arte aprovada', '9 x 5 cm', '4x4', 'Vias: 1'] as $campo) {
            $this->assertStringContainsString($campo, $html);
            $this->assertStringContainsString($campo, $xml);
        }
        $temporario = tempnam(sys_get_temp_dir(), 'teste-logo-');
        file_put_contents($temporario, app(GerarDocumentoOrdemServico::class)->gerar($ordem));
        $zip = new \ZipArchive;
        try {
            $this->assertTrue($zip->open($temporario));
            $this->assertSame(file_get_contents(public_path('img/logo-gold-press.png')), $zip->getFromName('word/media/section_image1.png'));
            $zip->close();
        } finally {
            unlink($temporario);
        }
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_limpeza_remove_so_documentos_de_os_e_permite_simulacao(): void
    {
        Storage::fake('public');
        $disco = Storage::disk('public');
        foreach (['ordens/antiga/os.docx', 'ordens/antiga/os.pdf', 'ordens/antiga/arte.png', 'outros/contrato.docx'] as $arquivo) {
            $disco->put($arquivo, 'conteúdo de teste');
        }

        $this->artisan('ordens:limpar-documentos --dry-run')->assertSuccessful();
        $disco->assertExists('ordens/antiga/os.docx');
        $this->artisan('ordens:limpar-documentos')->assertSuccessful();

        $disco->assertMissing('ordens/antiga/os.docx');
        $disco->assertMissing('ordens/antiga/os.pdf');
        $disco->assertExists('ordens/antiga/arte.png');
        $disco->assertExists('outros/contrato.docx');
        $this->artisan('ordens:limpar-documentos')->assertSuccessful();
    }

    public function test_mensagens_distinguem_pagamento_pendente_parcial_e_quitado(): void
    {
        $this->entrar();
        $ordem = $this->criar();
        $mensagens = app(MensagensWhatsApp::class);
        $pendente = $mensagens->modelos($ordem->fresh());
        $this->assertStringContainsString('Pagamento pendente.', $pendente['pedido']);
        $this->assertStringNotContainsString('Já pago:', $pendente['pedido']);

        $ordem->pagamentos()->create(['chave' => (string) Str::uuid(), 'valor' => 30, 'data_pagamento' => today(), 'forma' => 'pix']);
        $parcial = $mensagens->modelos($ordem->fresh());
        $this->assertStringContainsString('Já pago: R$ 30,00', $parcial['retirada']);
        $this->assertStringContainsString('Saldo a pagar: R$ 70,00', $parcial['retirada']);

        $ordem->pagamentos()->create(['chave' => (string) Str::uuid(), 'valor' => 70, 'data_pagamento' => today(), 'forma' => 'pix']);
        $quitado = $mensagens->modelos($ordem->fresh());
        $this->assertStringContainsString('Pagamento quitado.', $quitado['envio']);
        $this->assertStringNotContainsString('Saldo a pagar', $quitado['envio']);
        $this->get(route('ordens.success', $ordem->token))->assertOk()->assertSee('Quitada')->assertSee('Ver pagamentos');
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
        $this->assertStringNotContainsString('<img src="https://exemplo.test/foto.png"', $html);
    }
}
