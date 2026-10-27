@extends('layout')
@section('title',$cliente->exists ? 'Editar cliente' : 'Novo cliente')
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('clientes.index') }}">← Clientes</a><h1>{{ $cliente->exists ? 'Editar cliente' : 'Novo cliente' }}</h1><p>Preencha os dados que serão usados nas ordens de serviço.</p></div></div>
<form method="POST" action="{{ $cliente->exists ? route('clientes.update',$cliente) : route('clientes.store') }}" class="osf-main">
@csrf @if($cliente->exists) @method('PUT') @endif

<section class="card osf-card">
<div class="osf-card-head"><span class="osf-step">01</span><div><h2>Identificação do cliente</h2><p>Dados e contato de quem solicita o serviço.</p></div></div>
<div class="osf-grid osf-grid-3">
<div class="osf-field osf-span-2"><label for="nome">Nome completo / razão social *</label><input id="nome" type="text" name="nome" value="{{ old('nome',$cliente->nome) }}" maxlength="255" required autocomplete="name">@error('nome')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field"><label for="documento">CPF / CNPJ</label><input id="documento" type="text" name="documento" data-mask="cpf-cnpj" value="{{ old('documento',$cliente->documento) }}" maxlength="18" inputmode="numeric">@error('documento')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field"><label for="telefone">WhatsApp com DDD *</label><input id="telefone" type="tel" name="telefone" data-mask="telefone" value="{{ old('telefone',$cliente->telefone) }}" maxlength="255" required autocomplete="tel">@error('telefone')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-span-2"><label for="email">E-mail</label><input id="email" type="email" name="email" value="{{ old('email',$cliente->email) }}" maxlength="255" autocomplete="email">@error('email')<small class="osf-error">{{ $message }}</small>@enderror</div>
</div>
</section>

<section class="card osf-card">
<div class="osf-card-head"><span class="osf-step">02</span><div><h2>Endereço do cliente</h2><p>Digite o CEP para preencher o endereço automaticamente.</p></div></div>
<div class="osf-grid osf-grid-6">
<div class="osf-field osf-col-2"><label for="cep">CEP</label><div class="osf-input-status"><input id="cep" type="text" name="cep" data-mask="cep" data-viacep value="{{ old('cep',$cliente->cep) }}" maxlength="9" inputmode="numeric" autocomplete="postal-code" placeholder="00000-000" aria-describedby="cep-feedback"><span class="osf-spinner" data-cep-spinner hidden aria-hidden="true"></span></div><small id="cep-feedback" class="osf-help" data-cep-feedback role="status" aria-live="polite"></small>@error('cep')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-col-4"><label for="endereco">Logradouro</label><input id="endereco" type="text" name="endereco" value="{{ old('endereco',$cliente->endereco) }}" maxlength="255" autocomplete="address-line1">@error('endereco')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-col-1"><label for="numero_endereco">Número</label><input id="numero_endereco" type="text" name="numero_endereco" value="{{ old('numero_endereco',$cliente->numero_endereco) }}" maxlength="20" autocomplete="address-line2">@error('numero_endereco')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-col-2"><label for="bairro">Bairro</label><input id="bairro" type="text" name="bairro" value="{{ old('bairro',$cliente->bairro) }}" maxlength="255" autocomplete="address-level3">@error('bairro')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-col-2"><label for="cidade">Cidade</label><input id="cidade" type="text" name="cidade" value="{{ old('cidade',$cliente->cidade) }}" maxlength="255" autocomplete="address-level2">@error('cidade')<small class="osf-error">{{ $message }}</small>@enderror</div>
<div class="osf-field osf-col-1"><label for="uf">UF</label><select id="uf" name="uf" autocomplete="address-level1"><option value="">—</option>@foreach(config('ufs') as $sigla => $nome)<option value="{{ $sigla }}" @selected(old('uf',$cliente->uf) === $sigla)>{{ $sigla }}</option>@endforeach</select>@error('uf')<small class="osf-error">{{ $message }}</small>@enderror</div>
</div>
</section>

<section class="card osf-card">
<div class="osf-card-head"><span class="osf-step">03</span><div><h2>Observações</h2><p>Informações adicionais para os próximos atendimentos.</p></div></div>
<div class="osf-field"><label for="observacoes">Observações gerais</label><textarea id="observacoes" name="observacoes" rows="3" maxlength="10000">{{ old('observacoes',$cliente->observacoes) }}</textarea>@error('observacoes')<small class="osf-error">{{ $message }}</small>@enderror</div>
</section>

<div class="form-actions"><a class="button button-secondary fit" href="{{ route('clientes.index') }}">Cancelar</a><button class="button fit" type="submit">{{ $cliente->exists ? 'Salvar alterações' : 'Cadastrar cliente' }} <x-icon name="check"/></button></div>
</form>
@endsection
