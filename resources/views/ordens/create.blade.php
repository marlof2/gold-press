@extends('layout')
@section('title', $ordem ? 'Editar OS' : 'Nova ordem de serviço')
@push('scripts')
<script src="{{ asset('js/os-form.js') }}?v={{ filemtime(public_path('js/os-form.js')) }}" defer></script>
@endpush
@section('content')
@php
    $itens = old('itens');
    if (! is_array($itens)) {
        $itens = $ordem?->itens->map(fn ($item) => [
            'produto_id' => $item->produto_id,
            'tipo' => $item->tipo->value,
            'descricao' => $item->descricao,
            'unidade' => $item->unidade,
            'codigo' => $item->codigo,
            'referencia' => $item->referencia,
            'formato' => $item->formato,
            'vias' => $item->vias,
            'cores' => $item->cores,
            'quantidade' => (float) $item->quantidade,
            'largura' => $item->largura !== null ? (float) $item->largura : null,
            'altura' => $item->altura !== null ? (float) $item->altura : null,
            'area' => (float) $item->area,
            'valor_unitario' => (float) $item->valor_unitario,
            'valor_m2' => (float) $item->valor_m2,
            'subtotal' => (float) $item->subtotal,
        ])->all() ?? [];
    }
    if ($itens === []) {
        $itens = [[]];
    }
    $ajuste = fn (string $campo) => number_format((float) old($campo, $ordem?->{$campo} ?? 0), 2, ',', '.');
@endphp
<div class="page-heading"><div><a class="back-link" href="{{ route('ordens.index') }}">← Ordens de serviço</a><h1>{{ $ordem ? 'Editar OS #'.str_pad((string)$ordem->id,6,'0',STR_PAD_LEFT) : 'Nova ordem de serviço' }}</h1><p>Preencha os cards abaixo. O resumo financeiro é atualizado em tempo real.</p></div></div>
<form method="POST" action="{{ $ordem ? route('ordens.update',$ordem) : route('ordens.store') }}" id="ordem-form" class="osf-form">
@csrf @if($ordem) @method('PUT') @endif
<div class="osf-layout">
<div class="osf-main">
<section class="card osf-card">
<div class="osf-card-head"><span class="osf-step">01</span><div><h2>Identificação da OS &amp; Cliente</h2><p>Número, status e contato de quem pediu.</p></div></div>
<div class="osf-grid osf-grid-3">
<div class="osf-field"><label for="os-numero">Número da OS</label><input id="os-numero" type="text" value="{{ $ordem ? '#'.str_pad((string)$ordem->id,6,'0',STR_PAD_LEFT) : 'Nova OS' }}" readonly></div>
<div class="osf-field"><label for="os-data">Data</label><input id="os-data" type="text" value="{{ ($ordem?->created_at ?? now())->format('d/m/Y') }}" readonly></div>
<div class="osf-field"><label for="status_id">Status *</label><select id="status_id" name="status_id" required>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string)old('status_id',$ordem?->status_id) === (string)$status->id)>{{ $status->nome }}</option>@endforeach</select>@error('status_id')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-span-2"><label for="cliente_id">Cliente *</label><select id="cliente_id" name="cliente_id" data-url="{{ url('/clientes') }}" required><option value="">Selecione o cliente</option>@foreach($clientes as $cliente)<option value="{{ $cliente->id }}" @selected((string)old('cliente_id',$ordem?->cliente_id ?? request('cliente_id')) === (string)$cliente->id)>{{ $cliente->nome }} · {{ $cliente->telefone }}</option>@endforeach</select>@error('cliente_id')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field"><label>Contato do cliente</label><div class="osf-chips"><span id="cliente-telefone">{{ $ordem?->telefone }}</span><span id="cliente-documento">{{ $ordem?->documento }}</span></div><small class="osf-help" id="cliente-feedback" role="status"></small></div>
</div>
<div class="osf-links"><a class="text-link" href="{{ route('clientes.create') }}">+ Cadastrar novo cliente</a>@if($ordem?->cliente)<a class="text-link" href="{{ route('clientes.edit',$ordem->cliente) }}">Editar cadastro do cliente <x-icon name="arrow"/></a>@endif</div>
</section>

<section class="card osf-card">
<div class="osf-card-head"><span class="osf-step">02</span><div><h2>Endereço de execução / entrega</h2><p>Digite o CEP para preencher o endereço automaticamente.</p></div></div>
<div class="osf-grid osf-grid-6">
<div class="osf-field osf-col-2"><label for="cep">CEP</label><div class="osf-input-status"><input id="cep" name="cep" type="text" value="{{ old('cep',$ordem?->cep) }}" maxlength="9" inputmode="numeric" autocomplete="postal-code" data-mask="cep" data-viacep placeholder="00000-000"><span class="osf-spinner" data-cep-spinner hidden aria-hidden="true"></span></div><small class="osf-help" data-cep-feedback role="status" aria-live="polite"></small></div>
<div class="osf-field osf-col-4"><label for="endereco">Logradouro</label><input id="endereco" name="endereco" type="text" value="{{ old('endereco',$ordem?->endereco) }}" maxlength="255" autocomplete="address-line1"></div>
<div class="osf-field osf-col-1"><label for="numero_endereco">Número</label><input id="numero_endereco" name="numero_endereco" type="text" value="{{ old('numero_endereco',$ordem?->numero_endereco) }}" maxlength="20" autocomplete="address-line2"></div>
<div class="osf-field osf-col-2"><label for="bairro">Bairro</label><input id="bairro" name="bairro" type="text" value="{{ old('bairro',$ordem?->bairro) }}" maxlength="255" autocomplete="address-level3"></div>
<div class="osf-field osf-col-3"><label for="cidade">Cidade</label><input id="cidade" name="cidade" type="text" value="{{ old('cidade',$ordem?->cidade) }}" maxlength="255" autocomplete="address-level2"></div>
<div class="osf-field osf-col-1"><label for="uf">UF</label><select id="uf" name="uf" autocomplete="address-level1"><option value="">—</option>@foreach(config('ufs') as $sigla => $nome)<option value="{{ $sigla }}" @selected(old('uf',$ordem?->uf) === $sigla)>{{ $sigla }}</option>@endforeach</select></div>
</div>
<label class="osf-switch"><input type="checkbox" id="usar-endereco-cliente" checked> Usar o endereço do cliente como base ao trocar o cliente</label>
</section>

<section class="card osf-card">
<div class="osf-card-head"><span class="osf-step">03</span><div><h2>Itens &amp; Produtos</h2><p>Adicione quantos itens precisar. O tipo de cobrança muda os campos exibidos.</p></div><button type="button" class="button button-secondary fit osf-head-button" data-item-adicionar><x-icon name="plus"/>Adicionar produto</button></div>
<div id="osf-itens" class="osf-itens">
@foreach($itens as $index => $item)
@include('ordens.partials.item', ['index' => $index, 'item' => $item, 'produtos' => $produtos])
@endforeach
</div>
<button type="button" class="osf-add-item" data-item-adicionar><x-icon name="plus"/><span>Adicionar produto</span></button>
<p class="osf-help">Selecione um produto do catálogo para preencher tipo, unidade e valores. Você pode ajustar tudo manualmente.</p>
</section>

<section class="card osf-card">
<div class="osf-card-head"><span class="osf-step">04</span><div><h2>Observações, condições &amp; prazos</h2><p>Combine a entrega e registre o que precisa ficar claro.</p></div></div>
<div class="osf-grid osf-grid-3">
<div class="osf-field"><label for="responsavel">Responsável</label><input id="responsavel" name="responsavel" type="text" value="{{ old('responsavel',$ordem?->responsavel) }}" maxlength="255"></div>
<div class="osf-field"><label for="data_aprovacao">Data de aprovação</label><input id="data_aprovacao" name="data_aprovacao" type="date" value="{{ old('data_aprovacao',$ordem?->data_aprovacao?->format('Y-m-d')) }}">@error('data_aprovacao')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field"><label for="data_entrega">Data de entrega</label><input id="data_entrega" name="data_entrega" type="date" value="{{ old('data_entrega',$ordem?->data_entrega?->format('Y-m-d')) }}">@error('data_entrega')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-span-3"><label for="condicoes_pagamento">Condições de pagamento</label><input id="condicoes_pagamento" name="condicoes_pagamento" type="text" value="{{ old('condicoes_pagamento',$ordem?->condicoes_pagamento) }}" maxlength="255" placeholder="Ex.: 50% na aprovação, 50% na entrega"></div>
<div class="osf-field osf-span-3"><label for="servico_realizado">Serviço realizado</label><textarea id="servico_realizado" name="servico_realizado" rows="2" maxlength="10000">{{ old('servico_realizado',$ordem?->servico_realizado) }}</textarea></div>
<div class="osf-field osf-span-3"><label for="observacoes">Observações gerais</label><textarea id="observacoes" name="observacoes" rows="3" maxlength="10000" placeholder="Acabamento, cores, detalhes de instalação...">{{ old('observacoes',$ordem?->observacoes) }}</textarea></div>
</div>
</section>
</div>

<aside class="osf-aside">
<section class="card osf-summary" id="osf-resumo">
<div class="osf-summary-head"><span class="eyebrow">RESUMO FINANCEIRO</span><h2>Total da OS</h2></div>
<dl class="osf-summary-list">
<div><dt>Itens</dt><dd data-resumo-itens>0</dd></div>
<div><dt>Qtd. unitários</dt><dd data-resumo-quantidade-unitario>0</dd></div>
<div><dt>Qtd. m²</dt><dd data-resumo-quantidade-m2>0</dd></div>
<div><dt>Área total</dt><dd data-resumo-area>0,0000 m²</dd></div>
<div><dt>Subtotal dos itens</dt><dd data-resumo-subtotal>R$ 0,00</dd></div>
</dl>
<div class="osf-field osf-summary-desconto"><label for="desconto">Desconto (R$)</label><input id="desconto" name="desconto" type="text" inputmode="decimal" value="{{ $ajuste('desconto') }}" data-mask="currency" data-ajuste maxlength="18"></div>
<div class="osf-summary-total"><span>Valor total</span><strong data-resumo-total>R$ 0,00</strong></div>
<p class="osf-summary-erro" data-resumo-erro hidden>Revise os ajustes: o total não pode ser negativo.</p>
<p class="osf-help">O servidor recalcula todos os valores ao salvar.</p>
</section>
</aside>
</div>

<div class="osf-actionbar">
<div class="osf-actionbar-total"><span>Total</span><strong data-resumo-total-mobile>R$ 0,00</strong></div>
<div class="osf-actionbar-buttons">
<a class="button button-ghost fit" href="{{ route('ordens.index') }}" data-cancelar>Cancelar</a>
<button class="button button-secondary fit" type="submit" name="acao" value="salvar_docx" data-submit><x-icon name="download"/>Salvar e baixar Word</button>
<button class="button fit" type="submit" name="acao" value="salvar" data-submit><x-icon name="check"/>Salvar OS</button>
</div>
</div>
</form>

<div class="osf-toasts" id="osf-toasts" aria-live="polite" aria-atomic="true"></div>
<datalist id="produtos-lista">
@foreach($produtos as $produto)
<option value="{{ $produto->nome }}" data-id="{{ $produto->id }}" data-tipo="{{ $produto->tipo->value }}" data-unidade="{{ $produto->unidade }}" data-valor-unitario="{{ (float) $produto->valor_unitario }}" data-valor-m2="{{ (float) $produto->valor_m2 }}"></option>
@endforeach
</datalist>
<template id="osf-item-template">
@include('ordens.partials.item', ['index' => '__INDEX__', 'item' => [], 'produtos' => $produtos])
</template>
@endsection
