<?php

namespace Tests\Feature;

use App\Models\ModeloWhatsApp;
use App\Models\OrdemServico;
use App\Models\Permissao;
use App\Models\User;
use App\Services\MensagensWhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelosWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_modelos_iniciais_sao_preservados_e_podem_ser_editados(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->assertDatabaseCount('modelos_whatsapp', 4);
        $this->get(route('mensagens-whatsapp.index'))->assertOk()->assertSee('Enviar ordem de serviço');
        $modelo = ModeloWhatsApp::where('chave', 'pedido')->firstOrFail();
        $this->get(route('mensagens-whatsapp.edit', $modelo))->assertOk()->assertSee($modelo->conteudo)
            ->assertSee('Prévia da mensagem')->assertSee('data-whatsapp-previa-dados', false)->assertSee('Maria Silva');
        $this->put(route('mensagens-whatsapp.update', $modelo), [
            'nome' => 'Resumo da OS', 'conteudo' => 'Olá, {cliente}! Pedido #{numero}. PDF: {documento}', 'posicao' => 5, 'chave' => 'outra',
        ])->assertRedirect(route('mensagens-whatsapp.index'))->assertSessionHasNoErrors();
        $this->assertSame('pedido', $modelo->fresh()->chave);
        $ordem = OrdemServico::factory()->create(['nome' => 'Maria']);
        $mensagens = app(MensagensWhatsApp::class)->modelos($ordem);
        $this->assertSame('Olá, Maria! Pedido #'.str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT).'. PDF: '.route('documentos.show', ['token' => $ordem->token, 'formato' => 'pdf']), $mensagens['pedido']);
    }

    public function test_nova_mensagem_aparece_na_selecao_da_os_com_dados_preenchidos(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->get(route('mensagens-whatsapp.create'))->assertOk()->assertSee('Campos disponíveis')
            ->assertSee('Prévia da mensagem')->assertSee('data-whatsapp-previa-texto', false)->assertSee('Maria Silva');
        $this->post(route('mensagens-whatsapp.store'), [
            'nome' => 'Cobrança', 'conteudo' => 'Olá, {cliente}! Saldo: {saldo}. Entrega: {entrega}', 'posicao' => 0,
        ])->assertRedirect(route('mensagens-whatsapp.index'))->assertSessionHasNoErrors();
        $modelo = ModeloWhatsApp::where('nome', 'Cobrança')->firstOrFail();
        $ordem = OrdemServico::factory()->create(['nome' => 'Ana {empresa}', 'valor_total_final' => 100, 'data_entrega' => null]);
        $this->assertSame('Olá, Ana {empresa}! Saldo: R$ 100,00. Entrega: A combinar', app(MensagensWhatsApp::class)->modelos($ordem)[$modelo->chave]);
        $this->get(route('ordens.acao', [$ordem, 'whatsapp']))->assertOk()->assertSee('Cobrança')->assertSee($modelo->chave)->assertSee('Gerenciar mensagens');
    }

    public function test_validacao_impede_modelo_invalido_e_preserva_conteudo_salvo(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $modelo = ModeloWhatsApp::where('chave', 'pedido')->firstOrFail();
        $conteudo = $modelo->conteudo;
        $this->put(route('mensagens-whatsapp.update', $modelo), ['nome' => '', 'conteudo' => '{campo_inexistente}', 'posicao' => -1])
            ->assertSessionHasErrors(['nome', 'conteudo', 'posicao']);
        $this->assertSame($conteudo, $modelo->fresh()->conteudo);
        $this->postJson(route('mensagens-whatsapp.store'), ['nome' => 'Inválida', 'conteudo' => ['texto'], 'posicao' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('conteudo');
        $this->post(route('mensagens-whatsapp.store'), ['nome' => $modelo->nome, 'conteudo' => str_repeat('a', 6001), 'posicao' => 1000])
            ->assertSessionHasErrors(['nome', 'conteudo', 'posicao']);
    }

    public function test_gerenciamento_exige_login_e_permissoes_sem_bloquear_envio_da_os(): void
    {
        $modelo = ModeloWhatsApp::firstOrFail();
        $this->get(route('mensagens-whatsapp.index'))->assertRedirect(route('login'));
        $user = User::factory()->operador()->create();
        $user->perfil->permissoes()->detach(Permissao::where('grupo', 'mensagens-whatsapp')->pluck('id'));
        $this->actingAs($user->fresh());
        $this->get(route('mensagens-whatsapp.index'))->assertForbidden();
        $this->get(route('mensagens-whatsapp.create'))->assertForbidden();
        $this->get(route('mensagens-whatsapp.edit', $modelo))->assertForbidden();
        $this->post(route('mensagens-whatsapp.store'), [])->assertForbidden();
        $this->put(route('mensagens-whatsapp.update', $modelo), [])->assertForbidden();
        $ordem = OrdemServico::factory()->create();
        $this->get(route('ordens.acao', [$ordem, 'whatsapp']))->assertOk()->assertDontSee('Gerenciar mensagens');
    }
}
