<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
use App\Services\GerarDocumentoOrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class OrdemServicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $itens
     * @return array<string, mixed>
     */
    private function dados(?array $itens = null): array
    {
        $cliente = Cliente::factory()->create(['nome' => 'Ana & Filhos <Gráfica>']);

        return [
            'cliente_id' => $cliente->id,
            'status_id' => Status::where('slug', 'orcamento')->value('id'),
            'itens' => $itens ?? [
                ['tipo' => 'unitario', 'descricao' => 'Impressão A4 <A&B>', 'unidade' => 'un', 'quantidade' => 4, 'valor_unitario' => 10],
                ['tipo' => 'm2', 'descricao' => 'Banner <A&B>', 'unidade' => 'm²', 'quantidade' => 1, 'largura' => 1.5, 'altura' => 2, 'valor_m2' => 50],
            ],
            'desconto' => 5,
            'responsavel' => 'Teste',
            'condicoes_pagamento' => '50% na aprovação',
            'observacoes' => 'Preservar observações',
            'servico_realizado' => 'Corte e acabamento',
            'data_aprovacao' => '2026-10-22',
            'data_entrega' => '2026-10-24',
            'endereco' => 'Rua Orozí',
            'numero_endereco' => '47',
            'bairro' => 'Centro',
            'cidade' => 'Salvador',
            'uf' => 'BA',
            'cep' => '40353-165',
        ];
    }

    public function test_formulario_disponivel(): void
    {
        $this->get(route('ordens.create'))->assertOk()
            ->assertSee('Nova ordem de serviço')
            ->assertSee('Itens & Produtos')
            ->assertSee('Adicionar produto')
            ->assertSee('RESUMO FINANCEIRO')
            ->assertSee('Qtd. unitários')
            ->assertSee('Qtd. m²')
            ->assertSee('Salvar e baixar Word');
    }

    public function test_cria_calcula_docx_e_whatsapp(): void
    {
        Storage::fake('public', ['url' => 'https://grafica.example/storage']);
        $response = $this->post('/ordens', [...$this->dados(), 'valor_total_final' => 0.01, 'total_m2' => 999]);
        $os = OrdemServico::with('itens')->firstOrFail();
        $response->assertRedirect(route('ordens.success', $os->token));

        $this->assertSame('3.0000', $os->total_m2);
        $this->assertSame('5.0000', $os->quantidade);
        $this->assertSame('190.00', $os->valor_total);
        $this->assertSame('190.00', $os->valor_produtos);
        $this->assertSame('185.00', $os->valor_total_final);
        $this->assertCount(2, $os->itens);
        $this->assertSame('40.00', $os->itens[0]->subtotal);
        $this->assertSame('3.0000', $os->itens[1]->area);
        $this->assertSame('150.00', $os->itens[1]->subtotal);
        $this->assertSame('Impressão A4 <A&B>', $os->descricao);

        Storage::disk('public')->assertExists($os->arquivo_path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('public')->path($os->arquivo_path)));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertStringContainsString('w:orient="portrait"', $xml);
        $this->assertStringContainsString('ANA &amp; FILHOS &lt;GRÁFICA&gt;', $xml);
        $this->assertStringNotContainsString('Cpl:', $xml);
        $this->assertStringContainsString('Impressão A4 &lt;A&amp;B&gt;', $xml);
        $this->assertStringContainsString('Banner &lt;A&amp;B&gt;', $xml);
        $this->assertStringContainsString('3,0000 m²', $xml);
        $this->assertStringContainsString('R$ 185,00', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));

        $this->get(route('ordens.success', $os->token))->assertOk()
            ->assertSee('Itens da OS')
            ->assertSee('Desconto')
            ->assertSee('data-os-acao', false)
            ->assertDontSee('Quantidade total');
        $this->get(route('ordens.acao', [$os, 'whatsapp']))->assertOk()
            ->assertSee('https://wa.me/5511999991234?text=', false)
            ->assertSee(rawurlencode(route('documentos.show', ['token' => $os->token, 'formato' => 'pdf'])), false)
            ->assertSee('Mensagem pronta para envio');
    }

    public function test_aceita_desconto_acima_de_nove_reais(): void
    {
        Storage::fake('public');
        $this->post('/ordens', [...$this->dados(), 'desconto' => 10])->assertSessionHasNoErrors();
        $os = OrdemServico::firstOrFail();
        $this->assertSame('180.00', $os->valor_total_final);
    }

    public function test_rejeita_total_negativo_e_cliente_inexistente(): void
    {
        $this->post('/ordens', [...$this->dados(), 'desconto' => 10000])->assertSessionHasErrors('desconto');
        $this->post('/ordens', [...$this->dados(), 'cliente_id' => 999999])->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_rejeita_item_sem_os_campos_condicionais(): void
    {
        $semValor = $this->dados([['tipo' => 'unitario', 'descricao' => 'Sem valor', 'quantidade' => 1]]);
        $this->post('/ordens', $semValor)->assertSessionHasErrors('itens.0.valor_unitario');

        $semMedidas = $this->dados([['tipo' => 'm2', 'descricao' => 'Sem medidas', 'quantidade' => 1, 'valor_m2' => 50]]);
        $this->post('/ordens', $semMedidas)->assertSessionHasErrors(['itens.0.largura', 'itens.0.altura']);

        $this->post('/ordens', [...$this->dados(), 'itens' => []])->assertSessionHasErrors('itens');
        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_aceita_entrega_sem_aprovacao_e_prefixo_55(): void
    {
        Storage::fake('public');
        $dados = $this->dados();
        Cliente::findOrFail($dados['cliente_id'])->update(['telefone' => '5511999991234']);
        $this->post('/ordens', [...$dados, 'data_aprovacao' => null, 'data_entrega' => '2026-12-20'])->assertSessionHasNoErrors();
        $os = OrdemServico::firstOrFail();
        $this->get(route('ordens.acao', [$os, 'whatsapp']))->assertOk()->assertSee('https://wa.me/5511999991234?text=', false);
    }

    public function test_salvar_e_baixar_word_redireciona_com_download(): void
    {
        Storage::fake('public');
        $this->post('/ordens', [...$this->dados(), 'acao' => 'salvar_docx'])->assertSessionHasNoErrors();
        $os = OrdemServico::firstOrFail();
        $this->get(route('ordens.success', ['token' => $os->token, 'baixar' => 1]))->assertOk()->assertSee('download-os');
    }

    public function test_falha_no_documento_desfaz_os_e_remove_arquivo(): void
    {
        Storage::fake('public');
        $this->mock(GerarDocumentoOrdemServico::class)->shouldReceive('gerar')->once()->andReturnUsing(function () {
            throw new \RuntimeException('Disco indisponível');
        });
        $this->post('/ordens', $this->dados())->assertSessionHasErrors('documento');
        $this->assertDatabaseCount('ordens_servico', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_token_inexistente_retorna_404(): void
    {
        $this->get('/ordens/00000000-0000-4000-8000-000000000000/sucesso')->assertNotFound();
    }
}
