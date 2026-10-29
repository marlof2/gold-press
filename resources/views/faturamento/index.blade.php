@extends('layout')
@section('title', 'Faturamento')
@section('content')
@php
    $moeda = fn ($centavos) => 'R$ '.number_format($centavos / 100, 2, ',', '.');
    $maximo = max(1, ...array_column($meses, 'faturado_centavos'));
@endphp
<div class="page-heading"><div><span class="eyebrow">RESULTADOS DA GRÁFICA</span><h1>Faturamento</h1><p>De {{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }} · OS emitidas e pagamentos recebidos.</p></div></div>
<section class="card table-card revenue-filters" aria-label="Filtro de período">
<form class="filter-form" method="GET" action="{{ route('faturamento.index') }}">
<label for="inicio">Data inicial<input id="inicio" name="inicio" type="date" value="{{ old('inicio', $inicio->toDateString()) }}" min="2000-01-01" max="2100-12-31" required></label>
<label for="fim">Data final<input id="fim" name="fim" type="date" value="{{ old('fim', $fim->toDateString()) }}" min="2000-01-01" max="2100-12-31" required></label>
<button class="button fit" type="submit"><x-icon name="search"/>Consultar período</button>
</form>
<nav class="revenue-shortcuts" aria-label="Períodos rápidos">
@foreach(['Este mês' => [now()->startOfMonth(), now()->endOfMonth()], 'Mês anterior' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()], 'Este ano' => [now()->startOfYear(), now()->endOfYear()], 'Ano anterior' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()]] as $rotulo => [$de, $ate])
<a href="{{ route('faturamento.index', ['inicio' => $de->toDateString(), 'fim' => $ate->toDateString()]) }}" @class(['revenue-period', 'is-selected' => $inicio->isSameDay($de) && $fim->isSameDay($ate)]) @if($inicio->isSameDay($de) && $fim->isSameDay($ate)) aria-current="true" @endif>{{ $rotulo }}</a>
@endforeach
</nav>
</section>
<div class="metrics revenue-metrics">
<article class="metric"><div><span>Faturamento do período</span><x-icon name="orders"/></div><strong>{{ $moeda($totais['faturado_centavos']) }}</strong><small>Valor final das OS, após descontos</small></article>
<article class="metric"><div><span>Recebido no período</span><x-icon name="payment"/></div><strong>{{ $moeda($totais['recebido_centavos']) }}</strong><small>Pagamentos pela data do recebimento</small></article>
<article class="metric"><div><span>Ordens emitidas</span><x-icon name="status"/></div><strong>{{ $totais['quantidade'] }}</strong><small>Canceladas não entram na contagem</small></article>
<article class="metric"><div><span>Ticket médio</span><x-icon name="box"/></div><strong>{{ $moeda($totais['ticket_centavos']) }}</strong><small>Faturamento dividido pelas OS emitidas</small></article>
</div>
@if(!$totais['quantidade'] && !$totais['recebido_centavos'])<div class="notice" role="status">Nenhuma movimentação encontrada neste período. Escolha outras datas para consultar.</div>@endif
<section class="card revenue-chart" aria-labelledby="revenue-chart-title">
<div class="section-heading"><div><h2 id="revenue-chart-title">Faturamento por mês</h2><p>Comparação dos valores das OS emitidas dentro do período selecionado.</p></div></div>
<div class="revenue-bars">
@foreach($meses as $mes)
<div class="revenue-bar-row"><span>{{ $mes['rotulo'] }}</span><div class="revenue-bar-track" aria-hidden="true"><div style="width:{{ round($mes['faturado_centavos'] / $maximo * 100, 2) }}%"></div></div><strong>{{ $moeda($mes['faturado_centavos']) }}</strong></div>
@endforeach
</div>
</section>
<section class="card table-card">
<div class="section-heading"><div><h2>Resumo mensal</h2><p>Meses sem movimento também aparecem. Clique no mês para consultar o mês completo.</p></div></div>
<div class="responsive-table"><table class="revenue-table"><thead><tr><th>Mês</th><th>OS emitidas</th><th>Descontos</th><th>Faturamento</th><th>Recebido</th></tr></thead><tbody>
@foreach($meses as $mes)
@php($referencia = \Carbon\CarbonImmutable::parse($mes['mes'].'-01'))
<tr><td data-label="Mês"><a class="record-title" href="{{ route('faturamento.index', ['inicio' => $referencia->toDateString(), 'fim' => $referencia->endOfMonth()->toDateString()]) }}">{{ $mes['rotulo'] }}</a></td><td data-label="OS emitidas">{{ $mes['quantidade'] }}</td><td data-label="Descontos">{{ $moeda($mes['descontos_centavos']) }}</td><td data-label="Faturamento" class="money">{{ $moeda($mes['faturado_centavos']) }}</td><td data-label="Recebido" class="money">{{ $moeda($mes['recebido_centavos']) }}</td></tr>
@endforeach
<tr class="revenue-total"><td data-label="Total">Total do período</td><td data-label="OS emitidas">{{ $totais['quantidade'] }}</td><td data-label="Descontos">{{ $moeda($totais['descontos_centavos']) }}</td><td data-label="Faturamento" class="money">{{ $moeda($totais['faturado_centavos']) }}</td><td data-label="Recebido" class="money">{{ $moeda($totais['recebido_centavos']) }}</td></tr>
</tbody></table></div>
</section>
<section class="card revenue-notes"><h2>Como os valores são calculados</h2><p><strong>Faturamento:</strong> soma do valor final das OS emitidas no período, já com descontos. Inclui orçamentos e exclui OS canceladas.</p><p><strong>Recebido:</strong> pagamentos efetivos na data informada, sem estornos, inclusive de OS emitidas em outros períodos. Cancelar uma OS não estorna automaticamente seus pagamentos.</p><p>Em períodos que começam ou terminam no meio do mês, o resumo considera somente os dias selecionados. Os recebimentos podem ser maiores ou menores que o faturamento do mesmo período.</p></section>
@endsection
