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
        $this->actingAs(User::factory()->create());
    }

    private function dados(): array
    {
        $cliente = Cliente::factory()->create(['nome' => 'Ana & Filhos <Gráfica>']);

        return ['cliente_id' => $cliente->id, 'status_id' => Status::where('slug', 'orcamento')->value('id'), 'nome' => 'Ana & Filhos <Gráfica>', 'descricao' => 'Banner <A&B>', 'telefone' => '(11) 99999-1234', 'altura' => 2, 'largura' => 1.5, 'quantidade' => 4, 'valor_produto_m2' => 20, 'arredondamento' => -0.50, 'acerto_valor' => 10, 'valor_servicos' => 30, 'deslocamento' => 15, 'desconto' => 5, 'situacao' => 'orcamento'];
    }

    public function test_formulario_disponivel(): void
    {
        $this->get(route('ordens.create'))->assertOk()->assertSee('Criar OS e gerar documento');
    }

    public function test_cria_calcula_docx_e_whatsapp(): void
    {
        Storage::fake('public', ['url' => 'https://grafica.example/storage']);
        $response = $this->post('/ordens', [...$this->dados(), 'valor_total_final' => 0.01, 'total_m2' => 999]);
        $os = OrdemServico::firstOrFail();
        $response->assertRedirect(route('ordens.success', $os->token));
        $this->assertSame('3.0000', $os->total_m2);
        $this->assertSame('60.00', $os->valor_total_m2);
        $this->assertSame('240.00', $os->valor_total);
        $this->assertSame('249.50', $os->valor_produtos);
        $this->assertSame('289.50', $os->valor_total_final);
        Storage::disk('public')->assertExists($os->arquivo_path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('public')->path($os->arquivo_path)));
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        $this->assertStringContainsString('Ana &amp; Filhos &lt;Gráfica&gt;', $xml);
        $this->assertStringContainsString('R$ 289,50', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->get(route('ordens.success', $os->token))->assertOk()
            ->assertSee('https://wa.me/5511999991234?text=', false)
            ->assertSee(rawurlencode('https://grafica.example/storage/'.$os->arquivo_path), false)
            ->assertSee('Enviar WhatsApp');
    }

    public function test_rejeita_total_negativo_e_cliente_inexistente(): void
    {
        $this->post('/ordens', [...$this->dados(), 'desconto' => 10000])->assertSessionHasErrors('desconto');
        $this->post('/ordens', [...$this->dados(), 'cliente_id' => 999999])->assertSessionHasErrors('cliente_id');
        $this->assertDatabaseCount('ordens_servico', 0);
    }

    public function test_aceita_entrega_sem_aprovacao_e_prefixo_55(): void
    {
        Storage::fake('public');
        $dados = $this->dados();
        Cliente::findOrFail($dados['cliente_id'])->update(['telefone' => '5511999991234']);
        $this->post('/ordens', [...$dados, 'data_entrega' => '2026-12-20'])->assertSessionHasNoErrors();
        $os = OrdemServico::firstOrFail();
        $this->get(route('ordens.success', $os->token))->assertSee('https://wa.me/5511999991234?text=', false);
    }

    public function test_falha_no_documento_desfaz_os_e_remove_arquivo(): void
    {
        Storage::fake('public');
        $this->mock(GerarDocumentoOrdemServico::class)->shouldReceive('gerar')->once()->andReturnUsing(function ($os) {
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
