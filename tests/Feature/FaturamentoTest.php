<?php

namespace Tests\Feature;

use App\Models\OrdemServico;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaturamentoTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(): User
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $usuario = User::factory()->admin()->create();
        $this->actingAs($usuario);

        return $usuario;
    }

    private function ordem(string $data, float $valor, float $desconto = 0, string $status = 'aprovada'): OrdemServico
    {
        return OrdemServico::factory()->create([
            'created_at' => $data, 'status_id' => Status::where('slug', $status)->value('id'),
            'valor_total' => $valor + $desconto, 'desconto' => $desconto, 'valor_total_final' => $valor,
        ]);
    }

    public function test_resumo_mensal_considera_valor_final_descontos_e_meses_sem_movimento(): void
    {
        $this->entrar();
        $this->ordem('2026-01-10 10:00:00', 90, 10);
        $this->ordem('2026-01-20 10:00:00', 60);
        $this->ordem('2026-03-01 10:00:00', 200, 20);
        $this->ordem('2026-02-01 10:00:00', 999, 0, 'cancelada');
        $this->ordem('2026-04-01 10:00:00', 500);

        $this->get(route('faturamento.index', ['inicio' => '2026-01-01', 'fim' => '2026-03-31']))->assertOk()
            ->assertViewHas('totais', fn ($totais) => $totais === ['faturado_centavos' => 35000, 'recebido_centavos' => 0, 'descontos_centavos' => 3000, 'quantidade' => 3, 'ticket_centavos' => 11667])
            ->assertViewHas('meses', fn ($meses) => count($meses) === 3 && $meses[0]['faturado_centavos'] === 15000 && $meses[1]['faturado_centavos'] === 0 && $meses[2]['faturado_centavos'] === 20000)
            ->assertSee('R$ 350,00')->assertSee('R$ 116,67');
    }

    public function test_periodo_inclui_dias_inicial_e_final_sem_incluir_dias_adjacentes(): void
    {
        $this->entrar();
        $this->ordem('2026-10-09 23:59:59', 1000);
        $this->ordem('2026-10-10 00:00:00', 10.10);
        $this->ordem('2026-10-20 23:59:59', 20.20);
        $this->ordem('2026-10-21 00:00:00', 2000);
        $this->get(route('faturamento.index', ['inicio' => '2026-10-10', 'fim' => '2026-10-20']))->assertOk()
            ->assertViewHas('totais', fn ($totais) => $totais['faturado_centavos'] === 3030 && $totais['quantidade'] === 2);
    }

    public function test_recebimentos_usam_data_do_pagamento_incluem_os_antigas_e_excluem_estornos(): void
    {
        $this->entrar();
        $antiga = $this->ordem('2025-12-10 10:00:00', 1000);
        $antiga->pagamentos()->create(['chave' => fake()->uuid(), 'valor' => 120.25, 'data_pagamento' => '2026-01-15', 'forma' => 'pix']);
        $antiga->pagamentos()->create(['chave' => fake()->uuid(), 'valor' => 50, 'data_pagamento' => '2026-02-01', 'forma' => 'pix']);
        $estornado = $antiga->pagamentos()->create(['chave' => fake()->uuid(), 'valor' => 90, 'data_pagamento' => '2026-01-20', 'forma' => 'pix']);
        $estornado->forceFill(['estornado_em' => now()])->save();
        $cancelada = $this->ordem('2026-01-10 10:00:00', 100, 0, 'cancelada');
        $cancelada->pagamentos()->create(['chave' => fake()->uuid(), 'valor' => 30, 'data_pagamento' => '2026-01-25', 'forma' => 'dinheiro']);

        $this->get(route('faturamento.index', ['inicio' => '2026-01-01', 'fim' => '2026-01-31']))->assertOk()
            ->assertViewHas('totais', fn ($totais) => $totais['recebido_centavos'] === 15025 && $totais['faturado_centavos'] === 0)
            ->assertViewHas('meses', fn ($meses) => $meses[0]['recebido_centavos'] === 15025);
    }

    public function test_orcamentos_entram_e_canceladas_legadas_sem_status_nao_entram(): void
    {
        $this->entrar();
        $this->ordem('2026-10-01', 75, 0, 'orcamento');
        $legada = $this->ordem('2026-10-01', 300);
        $legada->update(['status_id' => null, 'situacao' => 'Cancelada']);
        $this->get(route('faturamento.index'))->assertOk()
            ->assertViewHas('totais', fn ($totais) => $totais['faturado_centavos'] === 7500 && $totais['quantidade'] === 1);
    }

    public function test_padrao_mostra_ano_atual_e_periodo_vazio_nao_divide_por_zero(): void
    {
        $this->entrar();
        $this->get(route('faturamento.index'))->assertOk()
            ->assertViewHas('inicio', fn ($inicio) => $inicio->toDateString() === '2026-01-01')
            ->assertViewHas('fim', fn ($fim) => $fim->toDateString() === '2026-12-31')
            ->assertViewHas('meses', fn ($meses) => count($meses) === 12)
            ->assertViewHas('totais', fn ($totais) => $totais['ticket_centavos'] === 0)
            ->assertSee('Nenhuma movimentação encontrada');
    }

    public function test_valida_periodo_e_rejeita_datas_invalidas(): void
    {
        $this->entrar();
        $this->getJson(route('faturamento.index', ['inicio' => '2026-02-30', 'fim' => '2026-03-01']))->assertUnprocessable()->assertJsonValidationErrors('inicio');
        $this->getJson(route('faturamento.index', ['inicio' => '2026-10-31', 'fim' => '2026-10-01']))->assertUnprocessable()->assertJsonValidationErrors('fim');
    }

    public function test_exige_login_e_permissao_para_faturamento(): void
    {
        $this->get(route('faturamento.index'))->assertRedirect(route('login'));
        $usuario = $this->entrar();
        $usuario->perfil->permissoes()->detach(Permissao::where('slug', 'faturamento.visualizar')->value('id'));
        $usuario->unsetRelation('perfil');
        $this->get(route('faturamento.index'))->assertForbidden();
        $this->get(route('account'))->assertOk()->assertDontSee('menu-financeiro', false);
    }

    public function test_migracao_atribui_acesso_ao_faturamento_sem_restaurar_outras_permissoes(): void
    {
        $usuario = $this->entrar();
        $perfil = Perfil::create(['nome' => 'Financeiro', 'slug' => 'financeiro']);
        $usuario->perfil->permissoes()->detach(Permissao::where('slug', 'produtos.excluir')->value('id'));
        $migracao = require database_path('migrations/2026_10_29_135840_add_faturamento_permission.php');
        $migracao->down();
        $migracao->up();
        $this->assertTrue($usuario->fresh()->can('faturamento.visualizar'));
        $this->assertTrue($perfil->permissoes()->where('slug', 'faturamento.visualizar')->exists());
        $this->assertFalse($usuario->fresh()->can('produtos.excluir'));
    }
}
