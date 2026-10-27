@extends('layout')
@section('title','Detalhes da OS')
@section('content')
@php
    $numero = fn ($valor, $casas = 3) => rtrim(rtrim(number_format((float) $valor, $casas, ',', '.'), '0'), ',');
    $quantidadeUnitario = $ordem->itens->filter(fn ($item) => ! $item->tipo->isMetroQuadrado())->sum(fn ($item) => (float) $item->quantidade);
    $quantidadeM2 = $ordem->itens->filter(fn ($item) => $item->tipo->isMetroQuadrado())->sum(fn ($item) => (float) $item->quantidade);
@endphp
<div class="page-heading"><div><a class="back-link" href="{{ route('ordens.index') }}">← Ordens de serviço</a><h1>OS #{{ str_pad((string)$ordem->id,6,'0',STR_PAD_LEFT) }}</h1><p>{{ $ordem->descricao }} · Criada em {{ $ordem->created_at->format('d/m/Y') }}</p></div><div class="heading-actions"><a class="button button-secondary fit" href="{{ route('ordens.edit',$ordem) }}"><x-icon name="edit"/>Editar</a><a class="button fit" id="baixar-docx" href="{{ $url }}" download><x-icon name="download"/>Baixar DOCX</a><a class="button whatsapp fit" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">Enviar WhatsApp <x-icon name="arrow"/></a></div></div>
<div class="osf-detail-grid">
<div class="osf-detail-main">
<section class="card table-card">
<div class="section-heading"><div><h2>Itens da OS</h2><p>{{ $ordem->itens->count() }} {{ $ordem->itens->count() === 1 ? 'item' : 'itens' }} nesta ordem.</p></div><x-status-badge :status="$ordem->status"/></div>
<div class="responsive-table"><table><thead><tr><th>Item</th><th>Tipo</th><th>Qtd</th><th>Medidas</th><th>Valor</th><th>Subtotal</th></tr></thead><tbody>
@foreach($ordem->itens as $item)
<tr><td data-label="Item"><span class="record-title">{{ $item->descricao }}</span>@if($item->codigo || $item->referencia)<span class="record-subtitle">{{ collect([$item->codigo, $item->referencia])->filter()->join(' · ') }}</span>@endif</td><td data-label="Tipo"><span class="cell-text">{{ $item->tipo->label() }}</span></td><td data-label="Qtd"><span class="cell-text">{{ $numero($item->quantidade) }} {{ $item->unidade }}</span></td><td data-label="Medidas"><span class="cell-text">{{ $item->medidas() ?: '—' }}</span></td><td data-label="Valor"><span class="cell-text">@if($item->tipo->isMetroQuadrado())R$ {{ number_format((float)$item->valor_m2,2,',','.') }} / m² @else R$ {{ number_format((float)$item->valor_unitario,2,',','.') }} @endif</span></td><td data-label="Subtotal" class="money">R$ {{ number_format((float)$item->subtotal,2,',','.') }}</td></tr>
@endforeach
</tbody></table></div>
</section>
<section class="card"><div class="section-heading"><div><h2>Cliente &amp; endereço</h2><p>Dados gravados nesta OS.</p></div>@if($ordem->cliente)<a class="text-link" href="{{ route('clientes.show',$ordem->cliente) }}">Ver cadastro <x-icon name="arrow"/></a>@endif</div><dl class="details-list"><div><dt>Cliente</dt><dd>{{ $ordem->nome }}</dd></div><div><dt>CPF / CNPJ</dt><dd>{{ $ordem->documento ?: '—' }}</dd></div><div><dt>Telefone</dt><dd>{{ $ordem->telefone }}</dd></div><div><dt>Endereço</dt><dd>{{ collect([$ordem->endereco,$ordem->numero_endereco,$ordem->bairro,$ordem->cidade,$ordem->uf,$ordem->cep])->filter()->join(' · ') ?: '—' }}</dd></div></dl></section>
<section class="card"><h2>Observações, condições &amp; prazos</h2><dl class="details-list"><div><dt>Responsável</dt><dd>{{ $ordem->responsavel ?: '—' }}</dd></div><div><dt>Aprovação</dt><dd>{{ $ordem->data_aprovacao?->format('d/m/Y') ?? '—' }}</dd></div><div><dt>Entrega</dt><dd>{{ $ordem->data_entrega?->format('d/m/Y') ?? 'A combinar' }}</dd></div><div><dt>Pagamento</dt><dd>{{ $ordem->condicoes_pagamento ?: '—' }}</dd></div><div><dt>Serviço realizado</dt><dd>{{ $ordem->servico_realizado ?: '—' }}</dd></div><div><dt>Observações</dt><dd>{{ $ordem->observacoes ?: '—' }}</dd></div></dl></section>
</div>
<aside class="osf-detail-aside">
<section class="card osf-summary"><div class="osf-summary-head"><span class="eyebrow">RESUMO FINANCEIRO</span><h2>Total da OS</h2></div><dl class="osf-summary-list"><div><dt>Itens</dt><dd>{{ $ordem->itens->count() }}</dd></div><div><dt>Qtd. unitários</dt><dd>{{ $numero($quantidadeUnitario) }}</dd></div>
<div><dt>Qtd. m²</dt><dd>{{ $numero($quantidadeM2) }}</dd></div><div><dt>Área total</dt><dd>{{ number_format((float)$ordem->total_m2,4,',','.') }} m²</dd></div><div><dt>Subtotal dos itens</dt><dd>R$ {{ number_format((float)$ordem->valor_total,2,',','.') }}</dd></div><div><dt>Desconto</dt><dd>R$ {{ number_format((float)$ordem->desconto,2,',','.') }}</dd></div></dl><div class="osf-summary-total"><span>Valor total</span><strong>R$ {{ number_format((float)$ordem->valor_total_final,2,',','.') }}</strong></div></section>
<section class="card danger-zone"><h2>Excluir ordem</h2><p>A OS e seu documento serão excluídos permanentemente.</p><form method="POST" action="{{ route('ordens.destroy',$ordem) }}" data-confirm="Excluir permanentemente a OS e seu documento?">@csrf @method('DELETE')<button type="submit" class="button button-danger fit">Excluir ordem</button></form></section>
</aside>
</div>
@if(request('baixar'))
<script>window.addEventListener('load', () => document.getElementById('baixar-docx')?.click());</script>
@endif
@endsection
