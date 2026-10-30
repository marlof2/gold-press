@extends('layout')
@section('title', $modelo->exists ? 'Editar mensagem' : 'Nova mensagem')
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('mensagens-whatsapp.index') }}">← Mensagens do WhatsApp</a><h1>{{ $modelo->exists ? 'Editar mensagem' : 'Nova mensagem' }}</h1><p>O modelo salvo ficará disponível em todas as ordens de serviço.</p></div></div>
<div class="wa-template-layout" data-whatsapp-previa>
<section class="card form-card"><form method="POST" action="{{ $modelo->exists ? route('mensagens-whatsapp.update', $modelo) : route('mensagens-whatsapp.store') }}">
@csrf
@if($modelo->exists) @method('PUT') @endif
<div class="fields">
<label for="nome">Nome da mensagem *<input id="nome" name="nome" value="{{ old('nome', $modelo->nome) }}" maxlength="80" required placeholder="Ex.: Lembrete de pagamento"></label>
<label for="posicao">Posição na lista *<input id="posicao" name="posicao" type="number" min="0" max="999" value="{{ old('posicao', $modelo->posicao) }}" required><small>Os menores números aparecem primeiro.</small></label>
<label class="wide" for="conteudo">Texto da mensagem *<textarea id="conteudo" name="conteudo" rows="14" maxlength="6000" required placeholder="Olá, {cliente}! O saldo do pedido #{numero} é {saldo}.">{{ old('conteudo', $modelo->conteudo) }}</textarea><small>Use os campos abaixo para preencher os dados de cada OS automaticamente. Você poderá revisar a mensagem antes de abrir o WhatsApp.</small></label>
</div>
<div class="form-actions"><a class="button button-secondary fit" href="{{ route('mensagens-whatsapp.index') }}">Cancelar</a><button class="button fit" type="submit">Salvar mensagem <x-icon name="check"/></button></div>
</form></section>
<aside class="card wa-template-preview" aria-labelledby="whatsapp-previa-titulo">
<h2 id="whatsapp-previa-titulo">Prévia da mensagem</h2>
<p>Dados de exemplo. Na OS, os campos serão preenchidos com os dados do pedido.</p>
<div class="wa-template-conversation"><div class="wa-template-bubble" data-whatsapp-previa-texto aria-live="polite" aria-atomic="true"></div></div>
<small class="hint" data-whatsapp-previa-aviso></small>
<noscript>Ative o JavaScript para visualizar a mensagem enquanto digita.</noscript>
</aside>
<script type="application/json" data-whatsapp-previa-dados>@json($exemplo)</script>
</div>
<section class="card"><h2>Campos disponíveis</h2><p>Copie o campo, incluindo as chaves, para o texto da mensagem. Exemplo: <code>{cliente}</code>.</p><dl>@foreach($variaveis as $chave => $descricao)<dt><code>{{ '{'.$chave.'}' }}</code></dt><dd>{{ $descricao }}</dd>@endforeach</dl></section>
@endsection
