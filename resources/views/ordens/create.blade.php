@extends('layout')
@section('title', $ordem ? 'Editar OS' : 'Nova ordem de serviço')
@section('content')
<div class="page-heading">
<div>
<span class="eyebrow">PRODUÇÃO / NOVA ORDEM</span>
<h1>{{ $ordem ? 'Editar OS #'.str_pad((string)$ordem->id,6,'0',STR_PAD_LEFT) : 'Nova ordem de serviço' }}</h1>
<p>Reúna os detalhes do pedido. Os valores são calculados enquanto você preenche.</p>
</div>
<a class="button button-secondary fit" href="{{ route('ordens.index') }}">Voltar às ordens</a>
</div>
<form method="POST" action="{{ $ordem ? route('ordens.update',$ordem) : route('ordens.store') }}" id="ordem-form">@csrf @if($ordem) @method('PUT') @endif
<div class="form-layout">
<div>
<section class="card">
<h2>
<span>01</span>Cliente</h2>
<div class="fields">
<label class="wide" for="cliente_id">Selecionar cliente *<select name="cliente_id" id="cliente_id" data-url="{{ url('/clientes') }}" required>
<option value="">Escolha um cliente cadastrado</option>@foreach($clientes as $cliente)<option value="{{ $cliente->id }}" @selected((string)old('cliente_id',$ordem?->cliente_id ?? request('cliente_id')) === (string)$cliente->id)>{{ $cliente->nome }} · {{ $cliente->telefone }}</option>@endforeach</select>
</label>
<div class="client-form-hint wide">
<p class="hint">Os dados abaixo vêm do cadastro do cliente.</p>
<a class="text-link" href="{{ route('clientes.create') }}">+ Cadastrar cliente</a>
</div>
<p id="cliente-feedback" class="hint wide" role="status" hidden>
</p>
<label class="wide" for="nome">Nome do cliente<input readonly id="nome" name="nome" type="text" value="{{ old('nome', $ordem?->nome ?? '') }}" maxlength="255" required>
</label>
<label class="" for="documento">CPF / CNPJ<input readonly id="documento" name="documento" type="text" value="{{ old('documento', $ordem?->documento ?? '') }}" maxlength="255">
</label>
<label class="" for="telefone">WhatsApp com DDD<input readonly id="telefone" name="telefone" type="tel" value="{{ old('telefone', $ordem?->telefone ?? '') }}" maxlength="255" required>
</label>
<label class="wide" for="endereco">Endereço<input readonly id="endereco" name="endereco" type="text" value="{{ old('endereco', $ordem?->endereco ?? '') }}" maxlength="255">
</label>
<label class="" for="bairro">Bairro<input readonly id="bairro" name="bairro" type="text" value="{{ old('bairro', $ordem?->bairro ?? '') }}" maxlength="255">
</label>
<label class="" for="cidade">Cidade<input readonly id="cidade" name="cidade" type="text" value="{{ old('cidade', $ordem?->cidade ?? '') }}" maxlength="255">
</label>
<label class="" for="uf">UF<input readonly id="uf" name="uf" type="text" value="{{ old('uf', $ordem?->uf ?? '') }}" maxlength="255">
</label>
<label class="" for="cep">CEP<input readonly id="cep" name="cep" type="text" value="{{ old('cep', $ordem?->cep ?? '') }}" maxlength="255">
</label>
</div>
</section>
<section class="card">
<h2>
<span>02</span>Produto</h2>
<div class="fields">
<label class="wide" for="descricao">Descrição do produto<input id="descricao" name="descricao" type="text" value="{{ old('descricao', $ordem?->descricao ?? '') }}" maxlength="255" required>
</label>
<label class="" for="codigo">Código<input id="codigo" name="codigo" type="text" value="{{ old('codigo', $ordem?->codigo ?? '') }}" maxlength="255">
</label>
<label class="" for="referencia">Referência<input id="referencia" name="referencia" type="text" value="{{ old('referencia', $ordem?->referencia ?? '') }}" maxlength="255">
</label>
<label class="" for="unidade">Unidade<input id="unidade" name="unidade" type="text" value="{{ old('unidade', $ordem?->unidade ?? '') }}" maxlength="255">
</label>
<label class="" for="formato">Formato<input id="formato" name="formato" type="text" value="{{ old('formato', $ordem?->formato ?? '') }}" maxlength="255">
</label>
<label class="" for="vias">Vias<input id="vias" name="vias" type="text" value="{{ old('vias', $ordem?->vias ?? '') }}" maxlength="255">
</label>
<label class="" for="cores">Cores<input id="cores" name="cores" type="text" value="{{ old('cores', $ordem?->cores ?? '') }}" maxlength="255">
</label>
<label class="wide" for="complemento">Complemento<input id="complemento" name="complemento" type="text" value="{{ old('complemento', $ordem?->complemento ?? '') }}" maxlength="255">
</label>
</div>
</section>
<section class="card">
<h2>
<span>03</span>Medidas e valores</h2>
<div class="fields">
<label class="" for="altura">Altura (m)<input id="altura" name="altura" type="number" value="{{ old('altura', $ordem?->altura ?? 0) }}" step="0.0001" min="0" max="10000" data-calc>
</label>
<label class="" for="largura">Largura (m)<input id="largura" name="largura" type="number" value="{{ old('largura', $ordem?->largura ?? 0) }}" step="0.0001" min="0" max="10000" data-calc>
</label>
<label class="" for="quantidade">Quantidade<input id="quantidade" name="quantidade" type="number" value="{{ old('quantidade', $ordem?->quantidade ?? 1) }}" step="0.0001" min="0.0001" max="10000" data-calc required>
</label>
<label class="" for="valor_produto_m2">Preço por m² (R$)<input id="valor_produto_m2" name="valor_produto_m2" type="number" value="{{ old('valor_produto_m2', $ordem?->valor_produto_m2 ?? 0) }}" step="0.01" min="0" max="1000000" data-calc>
</label>
<label class="" for="arredondamento">Arredondamento (R$)<input id="arredondamento" name="arredondamento" type="number" value="{{ old('arredondamento', $ordem?->arredondamento ?? 0) }}" step="0.01" min="-1000000" max="1000000" data-calc>
</label>
<label class="" for="acerto_valor">Acerto de valor (R$)<input id="acerto_valor" name="acerto_valor" type="number" value="{{ old('acerto_valor', $ordem?->acerto_valor ?? 0) }}" step="0.01" min="-1000000" max="1000000" data-calc>
</label>
<p class="hint wide">Altura × largura em metros. Arredondamento e acerto são ajustes em reais: use valores negativos para reduzir.</p>
</div>
</section>
<section class="card">
<h2>
<span>04</span>Execução</h2>
<div class="fields">
<label class="" for="responsavel">Responsável<input id="responsavel" name="responsavel" type="text" value="{{ old('responsavel', $ordem?->responsavel ?? '') }}" maxlength="255">
</label>
<label class="" for="data_aprovacao">Data de aprovação<input id="data_aprovacao" name="data_aprovacao" type="date" value="{{ old('data_aprovacao', $ordem?->data_aprovacao?->format('Y-m-d') ?? '') }}">
</label>
<label class="" for="data_entrega">Data de entrega<input id="data_entrega" name="data_entrega" type="date" value="{{ old('data_entrega', $ordem?->data_entrega?->format('Y-m-d') ?? '') }}">
</label>
<label class="wide" for="condicoes_pagamento">Condições de pagamento<input id="condicoes_pagamento" name="condicoes_pagamento" type="text" value="{{ old('condicoes_pagamento', $ordem?->condicoes_pagamento ?? '') }}" maxlength="255">
</label>
<label for="status_id">Status *<select name="status_id" id="status_id" required>@foreach($statuses as $status)<option value="{{ $status->id }}" @selected((string)old('status_id',$ordem?->status_id) === (string)$status->id)>{{ $status->nome }}</option>@endforeach</select>
</label>
<label class="wide" for="servico_realizado">Serviço realizado<textarea name="servico_realizado" id="servico_realizado" maxlength="10000">{{ old('servico_realizado', $ordem?->servico_realizado) }}</textarea>
</label>
<label class="wide" for="observacoes">Observações<textarea name="observacoes" id="observacoes" maxlength="10000">{{ old('observacoes', $ordem?->observacoes) }}</textarea>
</label>
</div>
</section>
<section class="card">
<h2>
<span>05</span>Fechamento</h2>
<div class="fields">
<label class="" for="valor_servicos">Serviços (R$)<input id="valor_servicos" name="valor_servicos" type="number" value="{{ old('valor_servicos', $ordem?->valor_servicos ?? 0) }}" step="0.01" min="0" max="1000000" data-calc>
</label>
<label class="" for="deslocamento">Deslocamento (R$)<input id="deslocamento" name="deslocamento" type="number" value="{{ old('deslocamento', $ordem?->deslocamento ?? 0) }}" step="0.01" min="0" max="1000000" data-calc>
</label>
<label class="" for="desconto">Desconto (R$)<input id="desconto" name="desconto" type="number" value="{{ old('desconto', $ordem?->desconto ?? 0) }}" step="0.01" min="0" max="1000000" data-calc>
</label>
</div>
</section>
</div>
<aside>
<section class="summary card">
<span class="eyebrow">SEU PEDIDO EM NÚMEROS</span>
<h2>Resumo da OS</h2>
<div aria-live="polite" aria-atomic="true">
<dl>
<div>
<dt>Área por unidade</dt>
<dd>
<output id="total_m2">0,0000 m²</output>
</dd>
</div>
<div>
<dt>Valor por unidade</dt>
<dd>
<output id="valor_total_m2">R$ 0,00</output>
</dd>
</div>
<div>
<dt>Subtotal × quantidade</dt>
<dd>
<output id="valor_total">R$ 0,00</output>
</dd>
</div>
<div>
<dt>Produtos com ajustes</dt>
<dd>
<output id="valor_produtos">R$ 0,00</output>
</dd>
</div>
<div>
<dt>Serviços</dt>
<dd>
<output id="resumo_servicos">R$ 0,00</output>
</dd>
</div>
<div>
<dt>Deslocamento</dt>
<dd>
<output id="resumo_deslocamento">R$ 0,00</output>
</dd>
</div>
<div>
<dt>Desconto</dt>
<dd>
<output id="resumo_desconto">R$ 0,00</output>
</dd>
</div>
</dl>
<div class="grand-total">
<span>VALOR FINAL</span>
<output id="valor_total_final">R$ 0,00</output>
</div>
</div>
<p id="calculo-erro" class="errors" hidden>Revise os ajustes: o total não pode ser negativo.</p>
<button type="submit">{{ $ordem ? 'Salvar alterações e gerar DOCX' : 'Criar OS e gerar documento' }} <x-icon name="arrow"/>
</button>
<p class="hint">Após salvar, baixe o DOCX e envie o link pelo WhatsApp.</p>
<noscript>
<p>Ative o JavaScript para ver a prévia dos totais. O cálculo também é feito ao salvar.</p>
</noscript>
</section>
</aside>
</div>
</form>
@endsection
