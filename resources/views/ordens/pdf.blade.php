<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>OS #{{ str_pad((string) $ordem->id, 6, '0', STR_PAD_LEFT) }}</title>
<style>
@page { margin: 32px 36px 55px; }
body { font: 10.5px 'DejaVu Sans', sans-serif; color: #27372f; line-height: 1.35; }
h1, h2, p { margin: 0; }
table { width: 100%; border-collapse: collapse; table-layout: fixed; }
td, th { vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
.header { margin-bottom: 6px; }
.header td { vertical-align: middle; padding-bottom: 8px; }
.brand { width: 65%; }
.logo { width: 190px; height: auto; }
.company-name { font-size: 20px; font-weight: bold; }
.company { font-size: 9px; color: #65715f; }
.company-address { font-size: 9px; text-align: left; white-space: nowrap; margin: 4px 0; }
.company-contacts { text-align: left; margin-bottom: 10px; }
.document-title { font-size: 19px; text-transform: uppercase; letter-spacing: .3px; line-height: 1.2; text-align: center; margin: 12px 0 8px; page-break-after: avoid; }
.company-details { width: 35%; text-align: right; font-size: 10px; }
.status { background: #d2691e; color: #fff; text-align: center; font-size: 17px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; padding: 5px 8px; margin-bottom: 16px; }
h2 { font-size: 10px; text-transform: uppercase; letter-spacing: .7px; color: #233b30; margin: 12px 0 6px; page-break-after: avoid; }
.info-row { margin: 0 0 7px; }
.info-field { display: inline-block; width: 61%; vertical-align: top; }
.info-field.dates { width: 38%; }
.label { font-weight: normal; }
.info strong { font-weight: bold; }
.items thead { display: table-header-group; }
.items th { background: #d2691e; color: #fff; font-size: 9px; padding: 9px 6px; border-bottom: 1px solid #d2691e; text-align: left; }
.items td { padding: 8px 6px; border-bottom: 1px solid #e5e9e3; font-size: 9px; }
.items .striped td { background: #fff3e8; }
.items tr { page-break-inside: avoid; }
.items .right, .right { text-align: right; }
.item-name { font-weight: bold; font-size: 10px; }
.item-details { color: #65715f; font-size: 8px; margin-top: 3px; }
.price { white-space: nowrap; }
.price-unit { font-size: 8px; color: #65715f; }
.financial { margin-top: 15px; page-break-inside: avoid; }
.totals td { padding: 6px 8px; border-bottom: 1px solid #e5e9e3; }
.totals td:last-child, .payments td:last-child { text-align: right; white-space: nowrap; font-weight: bold; }
.totals .grand td { background: #d2691e; color: #fff; font-size: 13px; }
.payments { margin-top: 8px; }
.payments td { padding: 6px 8px; border-bottom: 1px solid #e5e9e3; }
.payments .balance td { background: #edf2ed; }
.notes { white-space: pre-wrap; overflow-wrap: break-word; word-wrap: break-word; }
.closing { margin-top: 22px; page-break-inside: avoid; }
.closing td { width: 50%; vertical-align: bottom; font-weight: bold; }
.signature { display: inline-block; width: 210px; border-bottom: 1px solid #27372f; margin-left: 5px; }
</style>
</head>
<body>
@php
    $dados = app(\App\Services\DadosDocumentoOrdemServico::class)->para($ordem, $empresa);
@endphp
<table class="header"><tr>
<td class="brand">@if($dados['logo'])<img class="logo" src="{{ $dados['logo'] }}" alt="Logo Gold Press">@else<span class="company-name">{{ $dados['empresa'] }}</span>@endif</td>
<td class="company-details">@if($dados['empresa_telefone'])<p><strong>Telefone:</strong> {{ $dados['empresa_telefone'] }}</p>@endif
@if($dados['empresa_documento'])<p><strong>CNPJ:</strong> {{ $dados['empresa_documento'] }}</p>@endif</td>
</tr></table>
@if($dados['empresa_endereco'])<p class="company-address">{{ $dados['empresa_endereco'] }}</p>@endif
@if($dados['empresa_contatos'])<p class="company company-contacts">{{ $dados['empresa_contatos'] }}</p>@endif
<h1 class="document-title">ORDEM DE SERVIÇO #{{ $dados['numero'] }}</h1>
<div class="status">{{ $dados['status'] }}</div>
<div class="info">
<p class="info-row"><span class="info-field"><span class="label">Cliente:</span> <strong>{{ $dados['cliente'] }}</strong></span><span class="info-field dates"><span class="label">{{ $dados['documento_rotulo'] }}:</span> <strong>{{ $dados['documento'] }}</strong></span></p>
<p class="info-row"><span class="info-field"><span class="label">Telefone:</span> <strong>{{ $dados['telefone'] }}</strong></span><span class="info-field dates"><span class="label">Emissão:</span> <strong>{{ $dados['prazos']['Emissão'] }}</strong></span></p>
<p class="info-row"><span class="label">Endereço de execução / entrega:</span> <strong>{{ $dados['endereco'] }}</strong></p>
<p class="info-row"><span class="info-field"><span class="label">Responsável da gráfica:</span> <strong>{{ $dados['responsavel'] }}</strong></span><span class="info-field dates"><span class="label">Entrega prevista:</span> <strong>{{ $dados['prazos']['Entrega prevista'] }}</strong></span></p>
@if(isset($dados['prazos']['Aprovação']))<p class="info-row"><span class="label">Aprovação:</span> <strong>{{ $dados['prazos']['Aprovação'] }}</strong></p>@endif
</div>
<h2>Itens e serviços</h2>
<table class="items">
<thead><tr><th style="width:34%">Produto / serviço</th><th style="width:10%">Qtd.</th><th style="width:22%">Medidas</th><th class="right" style="width:17%">Preço</th><th class="right" style="width:17%">Subtotal</th></tr></thead>
<tbody>
@foreach($dados['itens'] as $item)
<tr @class(['striped' => $loop->even])>
<td><div class="item-name">{{ $item['descricao'] }}</div>@foreach($item['detalhes'] as $detalhe)<div class="item-details">{{ $detalhe }}</div>@endforeach</td>
<td>{{ $item['quantidade'] }}</td>
<td>{{ $item['medidas'] }}</td>
<td class="right"><span class="price">{{ $item['preco'] }}</span><br><span class="price-unit">{{ $item['preco_unidade'] }}</span></td>
<td class="right"><span class="price">{{ $item['subtotal'] }}</span></td>
</tr>
@endforeach
</tbody>
</table>
<div class="financial">
<table class="totals">
@foreach(['Subtotal', 'Desconto', 'Total da OS'] as $rotulo)
<tr @class(['grand' => $rotulo === 'Total da OS'])><td>{{ $rotulo }}</td><td>{{ $dados['totais'][$rotulo] }}</td></tr>
@endforeach
</table>
<table class="payments">
@foreach(['Valor pago', 'Valor a pagar'] as $rotulo)
<tr @class(['balance' => $rotulo === 'Valor a pagar'])><td>{{ $rotulo }}</td><td>{{ $dados['totais'][$rotulo] }}</td></tr>
@endforeach
</table>
</div>
@foreach($dados['notas'] as $rotulo => $valor)
<h2>{{ $rotulo }}</h2><p class="notes">{{ $valor }}</p>
@endforeach
<table class="closing"><tr><td>Obrigado pela preferência!</td><td class="right">Visto<span class="signature">&nbsp;</span></td></tr></table>
</body>
</html>
