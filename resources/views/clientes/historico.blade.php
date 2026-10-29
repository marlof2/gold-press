<div data-cliente-historico>
<div class="osf-action-heading"><div><small>{{ $cliente->nome }} · {{ $ordens->total() }} OS</small><h2 id="os-acao-titulo">Histórico de ordens de serviço</h2></div><button class="icon-button" type="button" data-os-acao-fechar aria-label="Fechar histórico"><x-icon name="close"/></button></div>
@include('ordens.rows', ['mostrarCliente' => false, 'contexto' => 'historico-cliente', 'clienteFiltro' => $cliente])
<x-pagination :paginator="$ordens"/>
</div>
