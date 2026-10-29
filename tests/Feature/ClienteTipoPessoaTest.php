<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\OrdemServico;
use App\Models\Status;
use App\Models\User;
use App\Services\GerarDocumentoOrdemServico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClienteTipoPessoaTest extends TestCase
{
    use RefreshDatabase;

    public static function pessoas(): array
    {
        return [
            'pessoa física' => ['PF', '12345678901', 'CPF'],
            'pessoa jurídica' => ['PJ', '12345678000190', 'CNPJ'],
        ];
    }

    #[DataProvider('pessoas')]
    public function test_tipo_e_documento_sao_salvos_e_identificados_na_os_pdf_e_word(string $tipo, string $documento, string $rotulo): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $resposta = $this->postJson(route('clientes.store'), ['nome' => 'Cliente de teste', 'tipo_pessoa' => $tipo, 'documento' => $documento]);
        $resposta->assertCreated()->assertJsonPath('tipo_pessoa', $tipo);
        $cliente = Cliente::findOrFail($resposta->json('id'));
        $this->assertSame($tipo, $cliente->tipo_pessoa);
        $this->getJson(route('clientes.edit', $cliente))->assertOk()->assertJsonPath('tipo_pessoa', $tipo);

        $this->post(route('ordens.store'), [
            'cliente_id' => $cliente->id,
            'status_id' => Status::where('slug', 'orcamento')->value('id'),
            'itens' => [['tipo' => 'unitario', 'descricao' => 'Impressão', 'quantidade' => 1, 'valor_unitario' => 10]],
        ])->assertSessionHasNoErrors();
        $ordem = OrdemServico::firstOrFail();
        $this->assertSame($tipo, $ordem->tipo_pessoa);
        $html = view('ordens.pdf', ['ordem' => $ordem, 'empresa' => Empresa::atual()])->render();
        $this->assertStringContainsString($rotulo.':</span>', $html);
        $this->assertStringNotContainsString('CPF / CNPJ', $html);
        $arquivo = tempnam(sys_get_temp_dir(), 'tipo-pessoa-');
        try {
            file_put_contents($arquivo, app(GerarDocumentoOrdemServico::class)->gerar($ordem));
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($arquivo));
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertStringContainsString($rotulo.': ', $xml);
            $this->assertStringNotContainsString('CPF / CNPJ', $xml);
        } finally {
            unlink($arquivo);
        }
        $cliente->update(['tipo_pessoa' => $tipo === 'PF' ? 'PJ' : 'PF', 'documento' => null]);
        $this->assertSame($tipo, $ordem->fresh()->tipo_pessoa);
        $this->assertSame($rotulo, $ordem->fresh()->documentoRotulo());
    }

    #[DataProvider('pessoas')]
    public function test_rejeita_documento_incompativel_com_tipo(string $tipo, string $documento, string $rotulo): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $incompativel = $tipo === 'PF' ? '12345678000190' : '12345678901';
        $this->postJson(route('clientes.store'), ['nome' => 'Cliente', 'tipo_pessoa' => $tipo, 'documento' => $incompativel])
            ->assertUnprocessable()->assertJsonValidationErrors('documento');
        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_tipo_pode_ser_alterado_para_pj_sem_exigir_documento(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $cliente = Cliente::factory()->create();
        $this->putJson(route('clientes.update', $cliente), ['nome' => $cliente->nome, 'tipo_pessoa' => 'PJ', 'documento' => null])
            ->assertOk()->assertJsonPath('tipo_pessoa', 'PJ')->assertJsonPath('documento', null);
        $this->assertSame('PJ', $cliente->fresh()->tipo_pessoa);
        $this->assertSame('CNPJ', $cliente->fresh()->documentoRotulo());
        $this->postJson(route('clientes.store'), ['nome' => 'Tipo inválido', 'tipo_pessoa' => 'OUTRO'])
            ->assertUnprocessable()->assertJsonValidationErrors('tipo_pessoa');
    }

    public function test_inferencia_preserva_compatibilidade_com_cadastro_sem_tipo(): void
    {
        $this->actingAs(User::factory()->operador()->create());
        $this->postJson(route('clientes.store'), ['nome' => 'Empresa', 'documento' => '12.345.678/0001-90'])
            ->assertCreated()->assertJsonPath('tipo_pessoa', 'PJ')->assertJsonPath('documento', '12345678000190');
    }

    public function test_migracao_identifica_cpf_e_cnpj_nos_cadastros_e_ordens_existentes(): void
    {
        $fisica = Cliente::factory()->create(['documento' => '123.456.789-01']);
        $juridica = Cliente::factory()->create(['documento' => '12.345.678/0001-90']);
        $ordem = OrdemServico::factory()->create(['documento' => '12.345.678/0001-90']);
        $migracao = require database_path('migrations/2026_10_29_132047_add_tipo_pessoa_to_clientes_and_ordens_servico.php');
        $migracao->down();
        $migracao->up();

        $this->assertSame('PF', $fisica->fresh()->tipo_pessoa);
        $this->assertSame('PJ', $juridica->fresh()->tipo_pessoa);
        $this->assertSame('PJ', $ordem->fresh()->tipo_pessoa);
    }
}
