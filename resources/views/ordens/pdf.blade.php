<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>OS #{{ str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT) }}</title>
<style>
@page { margin: 32px 36px 55px; }
body { font: 10.5px 'DejaVu Sans', sans-serif; color: #27372f; line-height: 1.5; }
h1, h2, p { margin: 0; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
td, th { vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
.header { border-bottom: 2px solid #b28d44; margin-bottom: 18px; }
.header td { padding-bottom: 14px; }
.logo { width: 200px; height: auto; margin-bottom: 7px; }
.company { font-size: 9px; color: #65715f; margin-top: 4px; }
.document { width: 35%; text-align: right; }
.document h1 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
.order-number { font-size: 23px; font-weight: bold; color: #233b30; }
.status { font-size: 9px; color: #65715f; }
h2 { font-size: 10px; text-transform: uppercase; letter-spacing: .7px; color: #233b30; margin: 17px 0 8px; page-break-after: avoid; }
.info td { padding: 0 12px 10px 0; }
.info .dates { width: 32%; padding-right: 0; }
.label { font-size: 8px; color: #65715f; text-transform: uppercase; letter-spacing: .4px; }
.client-name { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
.meta { margin-top: 7px; }
.items thead { display: table-header-group; }
.items th { background: #edf2ed; font-size: 9px; padding: 9px 6px; border-bottom: 1px solid #cbd5c4; text-align: left; }
.items td { padding: 10px 6px; border-bottom: 1px solid #e5e9e3; font-size: 9px; }
.items tr { page-break-inside: avoid; }
.items .right, .right { text-align: right; }
.item-name { font-weight: bold; font-size: 10px; }
.item-details { color: #65715f; font-size: 8px; margin-top: 3px; }
.price { white-space: nowrap; }
.price-unit { font-size: 8px; color: #65715f; }
.totals { width: 47%; margin: 15px 0 0 53%; page-break-inside: avoid; }
.totals td { padding: 4px 7px; }
.totals td:last-child { text-align: right; white-space: nowrap; font-weight: bold; }
.totals .grand td { border-top: 1px solid #b28d44; padding-top: 8px; font-size: 13px; color: #233b30; }
.totals .balance td { background: #edf2ed; padding-top: 7px; padding-bottom: 7px; }
.notes { white-space: pre-wrap; overflow-wrap: break-word; word-wrap: break-word; }
</style>
</head>
<body>
@php
    $dados = app(\App\Services\DadosDocumentoOrdemServico::class)->para($ordem, $empresa);
@endphp
<table class="header"><tr>
<td>@if($dados['logo'])<img class="logo" src="{{ $dados['logo'] }}" alt="Logo Gold Press"><br>@endif
<div class="company">@foreach($dados['empresa_linhas'] as $linha){{ $linha }}<br>@endforeach</div></td>
<td class="document"><h1>Ordem de serviço</h1><div class="order-number">#{{ $dados['numero'] }}</div><div class="status">{{ $dados['status'] }}</div></td>
</tr></table>
<table class="info"><tr>
<td><div class="label">Cliente</div><div class="client-name">{{ $dados['cliente'] }}</div><p>CPF / CNPJ: {{ $dados['documento'] }}<br>Telefone: {{ $dados['telefone'] }}</p><div class="meta"><span class="label">Endereço de execução / entrega</span><p>{{ $dados['endereco'] }}</p></div></td>
<td class="dates">@foreach($dados['prazos'] as $rotulo => $valor)<div class="meta"><span class="label">{{ $rotulo }}</span><p>{{ $valor }}</p></div>@endforeach</td>
</tr></table>
<p><span class="label">Responsável da gráfica:</span> {{ $dados['responsavel'] }}</p>
<h2>Itens e serviços</h2>
<table class="items">
<thead><tr><th style="width:34%">Produto / serviço</th><th style="width:10%">Qtd.</th><th style="width:22%">Medidas</th><th class="right" style="width:17%">Preço</th><th class="right" style="width:17%">Subtotal</th></tr></thead>
<tbody>
@foreach($dados['itens'] as $item)
<tr>
<td><div class="item-name">{{ $item['descricao'] }}</div>@foreach($item['detalhes'] as $detalhe)<div class="item-details">{{ $detalhe }}</div>@endforeach</td>
<td>{{ $item['quantidade'] }}</td>
<td>{{ $item['medidas'] }}</td>
<td class="right"><span class="price">{{ $item['preco'] }}</span><br><span class="price-unit">{{ $item['preco_unidade'] }}</span></td>
<td class="right"><span class="price">{{ $item['subtotal'] }}</span></td>
</tr>
@endforeach
</tbody>
</table>
<table class="totals">
@foreach($dados['totais'] as $rotulo => $valor)
<tr @class(['grand' => $rotulo === 'Total da OS', 'balance' => $rotulo === 'Saldo restante'])><td>{{ $rotulo }}</td><td>{{ $valor }}</td></tr>
@endforeach
</table>
@foreach($dados['notas'] as $rotulo => $valor)
<h2>{{ $rotulo }}</h2><p class="notes">{{ $valor }}</p>
@endforeach
</body>
</html>
